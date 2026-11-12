# Optional GenieACS integration: operations and MikroTik pilot

## Scope and tenant access

ACS is disabled by default for existing and new tenants. Only the ISP containing
`f.jafari1124@gmail.com` was authorized for this pilot (tenant
`c6cc2691-80ec-4437-b990-e8d3cb37e640`, company `hooshyar dorna`).
Do not enable it globally. The owner console's account management action **Enable /
disable ACS** controls the central `tenants.acs_enabled` entitlement and records an
audit reason. The tenant owner connects equipment in **Settings → ACS integration**
or **Customer Devices**. Existing subscriptions, RADIUS, billing and infrastructure
routers remain independent of ACS. Device assignment is optional.

Feature access, connection readiness, automatic provisioning and staff permissions
are separate controls. Automation starts OFF. Roles without `acs.read`,
`acs.write`, or `acs.actions` cannot perform the corresponding operations. Only
workspace owners configure connections, discover unassigned equipment and manage
profiles. Existing custom administrator roles are not silently granted permissions.
ACS permissions are hidden from role screens for disabled tenants.

## Installed production services

Host: `167.233.86.8`. Application host in the existing Nginx deployment:
`skybase.app` (no domain-based tenant resolution was introduced).

- GenieACS **1.2.16**, installed with `npm install --prefix /opt/skybase-acs genieacs@1.2.16 --omit=dev --no-audit --no-fund`.
- Existing server Node.js is used; no SkyBase Composer/npm dependencies were added.
- MongoDB **8.0.17** image digest:
  `sha256:9814652e33f0cf8b9fddea8b46dfc9d8e19b130dcfdd7b510ca58bb0d40c8b71`.
- Container: `skybase-acs-mongo`, restart `unless-stopped`, volume
  `skybase_acs_mongo`, loopback `127.0.0.1:27018` → container `27017`.
- Mongo authentication is enabled; memory limit 512 MiB, WiredTiger cache 0.25 GiB.
- `skybase-genieacs-cwmp.service`: loopback `127.0.0.1:7547`, one worker.
- `skybase-genieacs-nbi.service`: loopback `127.0.0.1:7557`, one worker.
- Units run as `genieacs`, with a private temporary directory, restricted filesystem,
  and per-unit memory limits. No standalone GenieACS admin UI or file service is exposed.
- `/opt/skybase-acs/genieacs.env` is root-readable and contains the Mongo connection
  and GenieACS settings. Never copy its contents into a ticket or repository.
- Root-only enrollment credentials: `/root/skybase-acs-enrollment.json`.
- `/etc/nginx/skybase-acs.htpasswd` contains a hash of the enrollment password.
- Existing TLS Nginx site forwards **`https://skybase.app/tr069/`** to CWMP, requiring
  Basic authentication over HTTPS. The original site configuration is backed up as
  `/root/nginx-skybase-before-acs.conf`.
- The management API and database are not published publicly. Keep them that way.

Inspect services without displaying secrets:

```bash
systemctl status skybase-genieacs-cwmp skybase-genieacs-nbi --no-pager
journalctl -u skybase-genieacs-cwmp -u skybase-genieacs-nbi -n 80 --no-pager
docker ps --filter name=skybase-acs-mongo
ss -ltn | grep -E '7547|7557|27018'
curl -fsS http://127.0.0.1:7557/devices/
nginx -t
```

A public request to `/tr069/` without credentials must return **401**.
Do not expose ports 7557 or 27018. The installer used for this deployment is retained
on the server at `/root/skybase_install_acs.py`; it generates credentials rather than
embedding them. Review it before reuse on another host. It assumes the existing
SkyBase Nginx site and Docker installation.

## SkyBase configuration and deployment

`config/acs.php` defaults to the approved local management endpoint
`http://127.0.0.1:7557`. Additional approved endpoints must be explicitly configured
using comma-separated `ACS_ALLOWED_ENDPOINTS` in the server's environment, followed
by `php artisan config:cache --no-interaction` and queue-worker restart.
A connection's normalized management URL is unique across tenants. For another ISP,
provision a separate GenieACS database/instance, enrollment credentials and approved
endpoint. A tag or a differently spelled URL pointing at the same shared backend
is not tenant isolation.

Production releases use `/srv/skybase/current` and shared `.env` / storage under
`/srv/skybase/shared`. This pilot is applied as a scoped overlay to base revision
`f906245e464414a7be4c0a30d955c107167b941a`. Unrelated local payment/IP-allocation changes
are deliberately excluded. Before the next Git-based deployment, commit/merge the
ACS source changes into the release branch; otherwise a normal deployment from the
old branch will remove the module. The deployed overlay is retained under
`/srv/skybase/patches/` for review/recovery. Do not upload the entire dirty checkout.

The additive migration `2026_11_10_202525_add_optional_acs_support.php` creates ACS
connections, customer devices, encrypted provisioning profiles and action history,
and adds the tenant entitlement defaulting to false. Do not run demo seeders in
production. Profile parameters and queued action payloads are encrypted and are not
returned by the activity endpoint. They require the existing application key.

After preparing a release with dependencies and built assets:

```bash
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
```

Use the existing atomic release symlink process and recycle Horizon/scheduler.
`acs:sync` is scheduled every five minutes and queues work only for enabled tenants.
For a targeted synchronization:

```bash
php artisan acs:sync --tenant=c6cc2691-80ec-4437-b990-e8d3cb37e640 --no-interaction
```

Existing Horizon default-queue workers process discovery and device commands.
The inventory screen shows the last successful synchronization. Discovery is queued,
not instantaneous. Device activity polls every ten seconds without reloading forms.

## MikroTik hAP lite onboarding (physical device still required)

The hAP lite was unavailable during implementation. The server/protocol tests used a
clearly identified simulated CPE; they are not evidence that a physical hAP lite's
Wi-Fi or WAN settings have been validated.

1. Record/export the existing configuration and serial number. Run:

   ```routeros
   /system resource print
   /system package print
   /system clock print
   ```

2. Check architecture, exact RouterOS version, free flash and RAM. hAP lite commonly
   uses `smips`; verify the actual device. Download the matching architecture and
   **exact version** extra-package archive from MikroTik's official downloads.
   Install `tr069-client` if absent and supported. Small flash is a constraint:
   do not install an incompatible package, force an upgrade, remove unrelated
   packages, or factory-reset the CPE. Package installation/reboot needs a maintenance
   window. If the package cannot fit, resolve this with the equipment owner first.
3. Verify internet/DNS reachability, accurate time, and trust for the HTTPS server
   certificate. Keep certificate verification enabled. Import the appropriate CA
   certificate on older RouterOS versions if required; do not disable TLS validation.
4. In WinBox **TR069**, set:

   - Enabled: yes.
   - ACS URL: `https://skybase.app/tr069/`.
   - Username/password: obtain securely from the administrator's root-only file.
     These are enrollment credentials, not SkyBase login credentials.
   - Periodic inform: enabled; interval 5 minutes for the pilot.

   Typical CLI form (confirm supported properties on the installed version):

   ```routeros
   /tr069-client set enabled=yes acs-url="https://skybase.app/tr069/" username="<enrollment-user>" password="<enrollment-password>" periodic-inform-enabled=yes periodic-inform-interval=5m
   /tr069-client print
   /log print where topics~"tr069"
   ```

   Do not put the substituted password into shared notes, screenshots or source files.
5. In SkyBase, open **ACS integration**, test the saved connection, then choose
   **Discover devices**. Refresh the inventory after the queue finishes.
6. Open the matching serial number; verify ownership. Select its customer, then an
   optional subscription. The subscription selector filters by the chosen customer.
   Saving assignment changes no device settings.
7. Request **Refresh device information**. Allow the next periodic inform when the
   router is behind NAT. Synchronize again to discover available writable parameters.
8. Test a harmless inventory refresh first. Reboot only with the customer ready for
   a brief interruption. Wi-Fi name changes are offered only for discovered writable
   SSID paths. Do not assume every RouterOS firmware exposes the same data model.
9. For provisioning, configure a profile using discovered string parameters. Exact
   manufacturer/model must match. Supported substitutions are `{{pppoe_username}}`
   and `{{pppoe_password}}`. Values are encrypted. Manually apply and verify the
   result before enabling **Automatic provisioning**.

No automatic firmware upgrade, factory reset, arbitrary RouterOS script execution,
or Wi-Fi password change is exposed by this release. Firmware campaigns and richer
diagnostics remain later work. Provisioning currently supports writable string
parameters; profiles are immutable saved snapshots, and an updated profile is saved
as a new record. There is no profile library/reuse workflow yet.

Commands are deliberately enqueued without `connection_request`. The initial live test
found GenieACS 1.2.16 could crash its NBI worker when immediate connection was requested
for a newly enrolled CPE missing connection-request parameters. Enqueuing also avoids
using unverified device-supplied callback addresses. Operations wait for periodic informs.

## Action semantics and recovery

- `queued`: SkyBase job waiting to run.
- `submitting`: command submission in progress.
- `pending`: GenieACS accepted the task; awaiting device execution.
- `completed`: immediate execution was confirmed, or a later inventory sync found
  no task/matching fault after a newer device contact. The latter is an inference;
  independently deleting ACS tasks can invalidate it.
- `failed`: matching ACS fault found.
- `uncertain`: submission failed/timed out and acceptance could not be established.
  Commands are not automatically retried in this state, preventing duplicate reboots.
- `cancelled`: capability/access/automation/service configuration changed before submission.

An outstanding command blocks another command and reassignment of that device.
For `uncertain`, inspect GenieACS tasks/faults and verify the CPE's actual state
before an administrator reconciles the record. A killed worker may leave `submitting`;
handle it with the same review. Do not blindly requeue it. An operator-facing
uncertainty-resolution workflow is not included yet.

Enabling ACS does not change billing or RADIUS status. Subscription lifecycle
changes can queue an assigned profile only when automation is enabled and the
subscription is active. Disabling automation/ACS cancels work not yet submitted
when the job executes; already accepted ACS commands are not revoked. Independent
GenieACS presets also continue running. Review/cancel those separately before
considering a disable operation a complete device-management shutdown.

## Verification

Targeted PHPUnit coverage lives in `tests/Feature/AcsModuleTest.php`. It covers
disabled navigation/settings/routes, permissions, ownership, tenant boundaries,
endpoint restrictions, encrypted secrets, command confirmation/deduplication,
queued versus completed states, submission uncertainty, fault reconciliation,
profiles and automation toggles. Existing owner-console, settings-navigation and
subscription-controller tests were also run.

`tests/Feature/GenieAcsLiveTest.php` is opt-in. Set `ACS_TEST_ENDPOINT`,
`ACS_TEST_CWMP_URL`, `ACS_TEST_USERNAME`, and `ACS_TEST_PASSWORD` in a protected test
process environment. When NBI is private, use an SSH forward. Never run migrations
or RefreshDatabase tests against the production database. The live test itself
uses no database migrations: it enrolls a unique `SkyBaseTest` simulated CPE,
queues/acknowledges a reboot RPC, and removes only that test device in `finally`.

```bash
php artisan test --compact tests/Feature/AcsModuleTest.php
php artisan test --compact tests/Feature/GenieAcsLiveTest.php
```

Back up the Mongo volume with authenticated Mongo tooling and retain the enrollment
file, encrypted application database, and application key in your existing secret
backup system. Review GenieACS/Mongo security releases before subsequent upgrades.

## Deployment verification results

The deployed release is `/srv/skybase/releases/20261110204053-acs-pilot`.
Authenticated production HTTP checks passed for inventory, connection settings,
connection test, discovery, device details and reboot submission. The full production
path (SkyBase form → Horizon job → GenieACS task → simulated CPE acknowledgement)
passed after removing immediate connection requests. Only uniquely named simulator
records/actions were removed afterward, and temporary HTTP test sessions were revoked.
A separate active tenant with ACS disabled returned 403 for `/acs`, with no ACS
settings or navigation. Real hardware acceptance testing remains outstanding.

The setup page was visually checked using the HTML rendered by production and its
existing production assets. Customer, subscription and ticket pages link to filtered
device inventories only for enabled tenants with ACS permission.

For rollback, pause ACS work first, atomically restore the previous release symlink,
and restart the existing Horizon/scheduler programs. The migration is additive;
keep the new tables and entitlement column during a code rollback to preserve data.
Do not migrate down on production merely to roll back the UI. To restore the original
Nginx enrollment configuration, review the saved Nginx backup, restore only that
configuration, run `nginx -t`, then reload Nginx. Stop the two `skybase-genieacs-*`
services if the pilot is being withdrawn; retain the Mongo volume for recovery.

Final checks: 21 ACS feature tests passed (including user-group visibility and the
regression preventing immediate connection requests), 15 ticketing tests passed,
and the live CWMP test passed with 9 assertions. The previously run settings,
owner-console and subscription regression checks also passed. Final production
state: exactly one ACS-enabled tenant, automation off, zero simulator devices left
in either SkyBase or GenieACS, both GenieACS services and Horizon/scheduler running.

Official references:
- https://docs.genieacs.com/en/stable/installation-guide.html
- https://docs.genieacs.com/en/stable/api-reference.html
- https://help.mikrotik.com/docs/spaces/ROS/pages/40992872/Packages
- https://help.mikrotik.com/docs/spaces/ROS/pages/9863195/TR-069
- https://manual.mikrotik.com/assets/tr069_parameters.html

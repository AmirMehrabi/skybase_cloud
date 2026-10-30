# SkyBase owner console implementation

## Scope and status
- [x] Phases 1–3 implemented in application code with a dummy payment gateway.
- [x] SaaS subscriptions and billing are separate from ISP internet-service plans, subscriber invoices, and subscriber payments.
- [x] On-premise billing remains in the owner's external CRM. All new billing and console switches default to disabled.
- [x] Existing tenant records remain unchanged until an explicit account action or backfill command.
- [x] Update this checklist at each implementation checkpoint.

## Phase 1 — boundaries and commercial foundation
- [x] Shared configuration catalog: Free 40/$0; Starter 150/$69; Growth 300/$129; Scale 600/$239; Business 1200/$399; Carrier 2400/$749 monthly USD.
- [x] Connect public cloud pricing-card prices/allowances and calculator to the shared catalog.
- [x] Capture inherited advertised features in package and invoice snapshots.
- [x] Enterprise calculator's $1300/5000 is indicative; public contact-sales terms take precedence for checkout. Enterprise self-service is disabled.
- [x] Separate console, SaaS billing and dummy gateway configuration switches.
- [x] Central permission gates for viewing, commercial management and suspension; only active central administrators receive these permissions.
- [x] Mandatory fresh MFA for console access, independent of tenant MFA settings.
- [x] Add tenant-scoped SkybaseAccount (subscription plus entitlements), SkybaseInvoice, SkybasePaymentAttempt, TenantAdminEvent and TenantNote models.
- [x] Integer minor-unit prices; stored agreed package/feature/price snapshots; existing ISP Plan records remain unchanged.
- [x] Add additive migration, factories, optional conservative seeder, and repeatable skybase:backfill-accounts command.
- [x] Backfill leaves commercial terms unconfigured; it never infers paid subscriptions.
- [x] Test central/tenant boundaries, deployment switches, context restoration and repeated initialization.

## Phase 2 — owner console
- [x] Tenant directory with search, pagination, access/archive filters, commercial-state and inactivity filters.
- [x] Active/suspended/archived totals, live customer/router/staff counts and last staff login (unknown displayed explicitly).
- [x] ISP detail with contacts, commercial terms, staff summary, internal notes, software invoices and administrative timeline.
- [x] Audited contact edits, package assignment, trial extension, subscriber allowance override and notes.
- [x] Audited suspend/reactivate/archive with mandatory reasons; no console hard deletion.
- [x] Application suspension uses a separate flag. It blocks tenant web access without changing the active status used by existing network/billing jobs.
- [x] Archive preserves account, invoice, note and audit records. Database foreign keys restrict deleting tenants with those records.
- [x] Central operations explicitly enter selected tenant context; notes/history have no tenant-facing routes.
- [x] Blade/Alpine views under resources/views/modules/owner; reusable navigation/invoice/input components.
- [x] HTML rendering exercised through authenticated feature tests; Blade compilation passes.
- [ ] Interactive desktop/mobile browser visual review (not performed in this environment).

## Phase 3 — SaaS billing with dummy gateway
- [x] Tenant-owner billing page, invoice history, package selection and payment outcome screen.
- [x] Paid checkout requires dummy gateway enablement and a non-production environment; free activation does not require a gateway.
- [x] Persist immutable invoice terms and an unpredictable tenant-scoped payment authority before presenting checkout.
- [x] Checkout ignores client amount/currency; prices originate from the server catalog.
- [x] Pending checkouts reuse a valid attempt; cancelled/failed/expired attempts can be retried.
- [x] Attempts expire after 30 minutes; cancellation/failure/expiry never mark invoices paid.
- [x] Settlement locks the tenant/invoice/attempt and atomically records account term, simulated invoice settlement, reference and audit event.
- [x] Repeated settlement cannot grant another month. Conflicting package settlement during an existing paid term is rejected.
- [x] Cancellation voids pending invoices and preserves the current paid term.
- [x] Package changes/renewal cancellation policy is visible in the billing page.
- [x] Manual monthly renewal adds one calendar month without day overflow; no automatic charges, proration, refunds or automatic overdue network disconnection.
- [x] Administrative package reassignment voids outstanding invoices; agreed commercial terms do not falsely record payment.
- [x] Simulated invoices and outcomes visibly say no money was collected; production simulation is blocked.
- [x] Tests cover forged package/amount, cross-tenant authority access, staff denial, duplicate settlement, retry, expiry, renewal, cancellation and production guard.
- [x] YekPay deferred at owner's request; no credentials copied or payment calls made.

## Phase 4 — remaining, outside current scope
- [ ] Define subscriber-counting policy consistently across customer/subscription lifecycles.
- [ ] Collect queued historical usage snapshots with stale/unknown indicators.
- [ ] Enforce subscriber limits atomically in create/import/API/background workflows.
- [ ] Enforce package feature entitlements in existing operational modules.
- [ ] Add tenant-aware error/provisioning metrics, deduplicated alerts and retention.
- [ ] Replace per-tenant live directory/filter reads with scalable summary records as tenant counts grow.
- [ ] Add automatic renewal reminders and commercial expiry/grace transitions. Current expiry is displayed/filtered; no automatic access changes occur.

## Phase 5 — rollout and remaining verification
- [x] Focused tests and existing security/subscriber-billing regression coverage pass: 33 tests, 180 assertions.
- [x] Pint passes; Blade templates compile; diff whitespace check passes.
- [ ] Frontend build: attempted npm run build, but npm/node are unavailable on this execution host.
- [ ] Interactive browser review of desktop/mobile forms and Alpine behavior.
- [ ] Apply migrations in target deployment. No development/live database migrations were applied; existing unrelated pending migrations were detected.
- [ ] Enable intended configuration switches after deployment prerequisites and MFA setup.
- [ ] Real YekPay integration, gateway callbacks/webhooks and sandbox/live validation.
- [ ] Optional full-suite run after owner review.

## Deployment procedure
1. Review pending migrations, including existing security/MFA and email-verification migrations; run the normal project migration process with `php artisan migrate --no-interaction` in the deployment environment.
2. For SaaS set SKYBASE_OWNER_CONSOLE_ENABLED=true and SKYBASE_SAAS_BILLING_ENABLED=true. Keep both false on customer on-premise installations.
3. For local/staging simulations only, set SKYBASE_DUMMY_GATEWAY_ENABLED=true. Production never permits simulated settlement.
4. Refresh cached configuration through the normal deployment process. Run `php artisan skybase:backfill-accounts --no-interaction` to initialize existing tenants without assigning paid terms.
5. Build frontend assets using `npm run build` on a host with the project's Node tooling.
6. Sign in with an active central super_admin identity (tenant_id null), complete MFA, then use the owner-console navigation. Tenant owners use the SkyBase subscription link.
7. Exercise a staging dummy checkout, cancellation/retry, and duplicate settlement before exposing the billing screens.

## Implementation checkpoints
- Foundation: added catalog and standalone guarded dummy adapter, verified their boundary tests.
- Phase 1: added persistence, deployment switches, permissions/MFA and conservative account initialization.
- Phase 2: added console views/actions, durable audit/notes and separate application-access suspension.
- Phase 3: integrated simulated checkout with server-priced invoices, tenant-scoped authorities, transactional settlement, cancellation and manual renewal policy.
- Verification: focused and regression tests pass; frontend build and interactive visual review remain explicitly unchecked above.

## Dedicated console hostname
- [x] Add console.skybase.app host boundary with a central-only identity provider.
- [x] Keep existing central User records and MFA enrollment; no identity migration required.
- [x] Host-only console session cookie; reject tenant credentials on console and central credentials on normal login.
- [x] End old central sessions on tenant/public hosts and send them to console sign-in.
- [x] Dedicated console login, shell, account navigation, readable tables and MFA screens.
- [x] Host authentication and existing console/security regression tests.
- [x] Production DNS/TLS, configuration activation and live hostname verification.
- [x] Verified separate login cookie has Secure/HttpOnly/SameSite=Lax and no Domain attribute; normal login remains available.
- [x] Verified production Vite build and all 13 manifest entries before release activation.
- [x] Added account summary cards, filter reset, and explicit confirmation for suspend/archive actions.

## Tenant deletion and operational oversight
- [x] Single-confirmation delete actions on the tenant directory and detail page.
- [x] Select individual tenants or all tenants on the current page and delete the selected batch with one confirmation; batches are limited to the 20 visible records.
- [x] Recoverable deletion removes tenants from normal queries, blocks tenant context/application access, and retains business/billing/audit records. No tenant data is permanently purged.
- [x] Dedicated central delete permission, validated confirmation and transactional audit event.
- [x] Customer/subscription totals, active/online counts, router/staff/support and overdue invoice summaries in directory rows.
- [x] Detailed state breakdowns, infrastructure/growth, recent subscriptions/tickets and subscriber billing balance on detail pages.
- [x] All operational reads execute in selected tenant context and explicitly filter tenant_id; monitoring states are labeled as last recorded.
- [x] Feature coverage for deletion confirmation/access denial/audit retention and tenant-isolated counts on both screens.
- [ ] Bulk deletion UI and request handling added; automated tests and interactive browser review not run for this update.

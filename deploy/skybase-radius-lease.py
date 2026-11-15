#!/usr/bin/env python3
"""Adapt FreeRADIUS exec input and reply pairs to the SkyBase lease API."""
import ipaddress
import json
import os
import pathlib
import re
import sys
import urllib.request

LEASE_CLASS = re.compile(r"skybase-lease:([0-9a-fA-F-]{36})\Z")


def allocation_pairs(reply):
    address = ipaddress.IPv4Address(reply["reply:Framed-IP-Address"]["value"])
    limit = int(reply["reply:Port-Limit"]["value"])
    interval = int(reply["reply:Acct-Interim-Interval"]["value"])
    lease_class = reply["reply:Class"]["value"]
    if not 2 <= limit <= 256 or not 1 <= interval <= 3600 or not LEASE_CLASS.fullmatch(lease_class):
        raise ValueError("Invalid allocation reply")
    return f'Framed-IP-Address := {address}, Port-Limit := {limit}, Acct-Interim-Interval := {interval}, Class += "{lease_class}"'


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        return None


def radius_attribute(name):
    return os.environ.get(name) or os.environ[name.upper().replace("-", "_")]


def main():
    action, source = sys.argv[1:]
    config = json.loads(pathlib.Path("/etc/freeradius/skybase-leases.json").read_text())
    router = config["routers"][str(ipaddress.ip_address(source))]
    body = {"router_id": router["router_id"], "username": radius_attribute("User-Name")}

    if action == "allocate":
        session_id = radius_attribute("Acct-Session-Id")
        if not session_id:
            raise ValueError("MikroTik Access-Request is missing Acct-Session-Id")
        body["session_key"] = session_id
    elif action == "accounting":
        raw_class = radius_attribute("Class")
        if raw_class.startswith("0x"):
            raw_class = bytes.fromhex(raw_class[2:]).decode("ascii")
        lease = LEASE_CLASS.fullmatch(raw_class)
        if not lease:
            raise ValueError("Accounting is missing the SkyBase lease token")
        body["lease_token"] = lease.group(1)
        body["event"] = {"Start": "start", "Interim-Update": "interim", "Stop": "stop"}[radius_attribute("Acct-Status-Type")]
    else:
        raise ValueError("Unknown lease operation")

    request = urllib.request.Request(
        config["url"].rstrip("/") + "/" + router["tenant_id"] + "/" + action,
        data=json.dumps(body).encode(),
        headers={
            "Authorization": "Bearer " + router["token"],
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
    )

    with urllib.request.build_opener(NoRedirect()).open(request, timeout=2) as response:
        reply = json.load(response)
    if action == "allocate":
        print(allocation_pairs(reply))


if __name__ == "__main__":
    try:
        main()
    except (KeyError, OSError, ValueError) as error:
        # URLs, bearer tokens and request attributes must stay out of radius logs.
        print("SkyBase lease request failed: " + type(error).__name__, file=sys.stdout)
        sys.exit(1)

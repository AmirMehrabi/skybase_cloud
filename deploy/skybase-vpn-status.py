#!/usr/bin/env python3
import grp
import json
import os
from pathlib import Path
import re
import tempfile

SOURCE = Path('/run/openvpn-server/status-server.log')
DESTINATION = Path('/var/lib/skybase-vpn')


def publish(name, contents):
    descriptor, temporary = tempfile.mkstemp(dir=DESTINATION)
    try:
        with os.fdopen(descriptor, 'w') as stream:
            stream.write(contents)
            stream.flush()
            os.fsync(stream.fileno())
        os.chmod(temporary, 0o640)
        os.chown(temporary, 0, grp.getgrnam('www-data').gr_gid)
        os.replace(temporary, DESTINATION / name)
    finally:
        Path(temporary).unlink(missing_ok=True)


DESTINATION.mkdir(mode=0o750, exist_ok=True)
os.chown(DESTINATION, 0, grp.getgrnam('www-data').gr_gid)
if SOURCE.is_file():
    contents = SOURCE.read_text()
    if contents.rstrip().endswith('END'):
        publish('status.csv', contents)
reservations = {}
for client in Path('/etc/openvpn/server/ccd').glob('*'):
    if client.is_file() and not client.is_symlink():
        match = re.search(r'^ifconfig-push\s+([\d.]+)\s+', client.read_text(), re.M)
        if match:
            reservations[client.name] = match[1]
publish('reservations.json', json.dumps(reservations))

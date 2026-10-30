#!/usr/bin/env python3
"""Authenticated GitHub intake. Deployment runs in a separate systemd service."""
import argparse
import hashlib
import hmac
import json
import os
import re
import tempfile
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path


def make_handler(secret, queue, repository):
    class Handler(BaseHTTPRequestHandler):
        def reply(self, status, message):
            body = json.dumps({'message': message}).encode()
            self.send_response(status)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Content-Length', str(len(body)))
            self.end_headers()
            self.wfile.write(body)

        def do_POST(self):
            if self.path != '/hooks/github/skybase':
                return self.reply(404, 'Not found')
            try:
                length = int(self.headers.get('Content-Length', '0'))
                if not 0 < length <= 2097152:
                    return self.reply(413, 'Invalid payload size')
                self.connection.settimeout(10)
                body = self.rfile.read(length)
                expected = 'sha256=' + hmac.new(secret, body, hashlib.sha256).hexdigest()
                if not hmac.compare_digest(expected, self.headers.get('X-Hub-Signature-256', '')):
                    return self.reply(403, 'Invalid signature')
                payload = json.loads(body)
                event = self.headers.get('X-GitHub-Event')
                if payload.get('repository', {}).get('full_name') != repository:
                    return self.reply(403, 'Unexpected repository')
                if event == 'ping':
                    return self.reply(200, 'Webhook ready')
                if event != 'push' or payload.get('ref') != 'refs/heads/master' or payload.get('deleted'):
                    return self.reply(200, 'Event ignored')
                revision = payload.get('after', '')
                delivery = self.headers.get('X-GitHub-Delivery', '')
                if not re.fullmatch(r'[0-9a-f]{40}', revision) or not re.fullmatch(r'[0-9a-fA-F-]{36}', delivery):
                    return self.reply(400, 'Invalid delivery')
                destination = queue / (delivery + '.json')
                if not destination.exists():
                    fd, name = tempfile.mkstemp(prefix='.incoming-', dir=queue)
                    try:
                        with os.fdopen(fd, 'w') as stream:
                            json.dump({'revision': revision, 'delivery': delivery}, stream)
                            stream.flush()
                            os.fsync(stream.fileno())
                        os.replace(name, destination)
                    finally:
                        if os.path.exists(name):
                            os.unlink(name)
                return self.reply(202, 'Deployment queued')
            except (ValueError, OSError, TypeError, AttributeError):
                return self.reply(400, 'Invalid request')

    return Handler


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--secret-file', default='/etc/skybase-deploy/secret')
    parser.add_argument('--queue', default='/var/lib/skybase-webhook/queue')
    parser.add_argument('--port', type=int, default=9108)
    parser.add_argument('--repository', default='AmirMehrabi/skybase_cloud')
    args = parser.parse_args()
    secret = Path(args.secret_file).read_bytes().strip()
    if len(secret) < 32:
        raise SystemExit('A webhook secret of at least 32 bytes is required')
    server = ThreadingHTTPServer(('127.0.0.1', args.port), make_handler(secret, Path(args.queue), args.repository))
    server.serve_forever()

#!/usr/bin/env python3
"""Build releases away from traffic and atomically activate a healthy release."""
import datetime
import fcntl
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import time

BASE = Path('/srv/skybase')
QUEUE = Path('/var/lib/skybase-webhook/queue')
LEGACY = Path('/var/www/html/skybase_cloud')
REPOSITORY = BASE / 'repository.git'
USER = 'skybase-build'


def run(args, cwd=None, build=False, capture=False, env=None):
    if build:
        args = ['runuser', '-u', USER, '--'] + args
    print('+', ' '.join(map(str, args)), flush=True)
    return subprocess.run(list(map(str, args)), cwd=cwd, check=True, text=True,
                          stdout=subprocess.PIPE if capture else None, env=env).stdout


def artisan(release, *args):
    environment = os.environ.copy()
    environment['VIEW_COMPILED_PATH'] = str(release / 'bootstrap/cache/views')
    run(['php', 'artisan', *args, '--no-interaction'], cwd=release, build=True, env=environment)


def verify_vite_assets(release):
    build = release / 'public/build'
    manifest_path = build / 'manifest.json'
    if not manifest_path.is_file():
        raise RuntimeError('Vite build did not produce public/build/manifest.json')

    manifest = json.loads(manifest_path.read_text())
    required = {
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/marketing.css',
        'resources/js/marketing.js',
    }
    missing_entries = required.difference(manifest)
    if missing_entries:
        raise RuntimeError('Vite manifest is missing required entries: ' + ', '.join(sorted(missing_entries)))

    for entry_name, entry in manifest.items():
        files = [entry.get('file'), *entry.get('css', []), *entry.get('assets', [])]
        for filename in filter(None, files):
            asset = (build / filename).resolve()
            if not asset.is_relative_to(build.resolve()) or not asset.is_file():
                raise RuntimeError(f'Vite output for {entry_name} is missing: {filename}')

    print(f'VERIFIED Vite build with {len(manifest)} manifest entries', flush=True)


def switch(target):
    temporary = BASE / 'current.next'
    temporary.unlink(missing_ok=True)
    temporary.symlink_to(target)
    os.replace(temporary, BASE / 'current')


def healthy():
    for attempt in range(10):
        try:
            run(['curl', '--fail', '--silent', '--show-error', '--max-time', '10',
                 '--resolve', 'skybase.app:443:127.0.0.1', 'https://skybase.app/up'], capture=True)
            return True
        except subprocess.CalledProcessError:
            time.sleep(2)
    return False


def recycle(release):
    artisan(release, 'horizon:terminate')
    run(['supervisorctl', 'signal', 'TERM', 'skybase-cloud-horizon'])
    artisan(release, 'queue:restart')
    artisan(release, 'schedule:interrupt')
    run(['supervisorctl', 'restart', 'skybase-cloud-scheduler'])


def deploy():
    run(['git', '--git-dir', REPOSITORY, 'fetch', '--quiet', 'origin', 'master:refs/heads/master'])
    revision = run(['git', '--git-dir', REPOSITORY, 'rev-parse', 'refs/heads/master'], capture=True).strip()
    previous = (BASE / 'current').resolve()
    if (previous / 'REVISION').exists() and (previous / 'REVISION').read_text().strip() == revision:
        print('Latest master is already active', flush=True)
        return
    name = datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%d%H%M%S') + '-' + revision[:12]
    release = BASE / 'releases' / name
    release.mkdir()
    archive = subprocess.Popen(['git', '--git-dir', str(REPOSITORY), 'archive', revision], stdout=subprocess.PIPE)
    try:
        subprocess.run(['tar', '-x', '-C', str(release)], stdin=archive.stdout, check=True)
    finally:
        archive.stdout.close()
    if archive.wait() != 0:
        raise RuntimeError('Git archive failed')
    shutil.rmtree(release / 'storage')
    (release / 'storage').symlink_to(BASE / 'shared/storage')
    (release / '.env').symlink_to(BASE / 'shared/.env')
    (release / 'bootstrap/cache/views').mkdir(parents=True, exist_ok=True)
    run(['chown', '-R', '--no-dereference', USER + ':www-data', release])
    run(['composer', 'install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader'], cwd=release, build=True)
    run(['npm', 'ci', '--no-audit', '--no-fund'], cwd=release, build=True)
    old_assets = previous / 'public/build/assets'
    if old_assets.is_dir():
        shutil.copytree(old_assets, release / 'public/build/assets', dirs_exist_ok=True)
    run(['npm', 'run', 'build'], cwd=release, build=True)
    verify_vite_assets(release)
    for asset in LEGACY.joinpath('public').iterdir():
        if asset.suffix in ('.mp4', '.qcow2') and not (release / 'public' / asset.name).exists():
            (release / 'public' / asset.name).symlink_to(asset)
    (release / 'public/storage').symlink_to(BASE / 'shared/storage/app/public')
    artisan(release, 'migrate', '--force')
    artisan(release, 'config:cache')
    artisan(release, 'route:cache')
    artisan(release, 'view:cache')
    (release / 'REVISION').write_text(revision + '\n')
    # Smoke-test the new app before Nginx sees it.
    server = subprocess.Popen(['runuser', '-u', USER, '--', 'php', '-S', '127.0.0.1:19109', '-t', 'public', 'public/index.php'], cwd=release, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for attempt in range(15):
            probe = subprocess.run(['curl', '-fsS', '--max-time', '5', '-H', 'Host: skybase.app', 'http://127.0.0.1:19109/up'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            if probe.returncode == 0:
                break
            time.sleep(1)
        else:
            raise RuntimeError('New release failed preflight health check')
    finally:
        server.terminate()
        server.wait(timeout=15)
    # Runtime code is read-only to both the web user and build user after preparation.
    run(['chown', '-R', '--no-dereference', 'root:www-data', release])
    run(['chmod', '-R', 'g+rwX', release / 'bootstrap/cache'])
    # The symlink and its temporary replacement are on the same filesystem.
    switch(release)
    try:
        if not healthy():
            raise RuntimeError('Live health check failed')
        recycle(release)
    except Exception:
        switch(previous)
        recycle(previous)
        raise
    (BASE / 'previous.next').unlink(missing_ok=True)
    (BASE / 'previous.next').symlink_to(previous)
    os.replace(BASE / 'previous.next', BASE / 'previous')
    (BASE / 'status.json').write_text(json.dumps({'revision': revision, 'release': str(release), 'deployed_at': datetime.datetime.now(datetime.timezone.utc).isoformat()}))
    protected = {release.resolve(), previous.resolve()}
    successful = sorted((BASE / 'releases').iterdir(), reverse=True)
    for old_release in successful[5:]:
        if old_release.is_dir() and not old_release.is_symlink() and old_release.resolve() not in protected and (old_release / 'REVISION').is_file() and time.time() - old_release.stat().st_mtime > 86400:
            shutil.rmtree(old_release)
    print('DEPLOYED ' + revision, flush=True)


def main():
    with open(BASE / 'deploy.lock', 'w') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        if '--rollback' in sys.argv:
            previous = (BASE / 'previous').resolve(strict=True)
            current = (BASE / 'current').resolve(strict=True)
            switch(previous)
            if not healthy():
                switch(current)
                raise RuntimeError('Rollback health check failed')
            recycle(previous)
            (BASE / 'previous.next').unlink(missing_ok=True)
            (BASE / 'previous.next').symlink_to(current)
            os.replace(BASE / 'previous.next', BASE / 'previous')
            revision = (previous / 'REVISION').read_text().strip() if (previous / 'REVISION').exists() else 'legacy'
            (BASE / 'status.json').write_text(json.dumps({'revision': revision, 'release': str(previous), 'rolled_back_at': datetime.datetime.now(datetime.timezone.utc).isoformat()}))
            print('ROLLED BACK to ' + str(previous), flush=True)
        elif '--now' in sys.argv:
            deploy()
        else:
            for request in sorted(QUEUE.glob('*.json')):
                try:
                    deploy()
                except Exception:
                    request.rename(BASE / 'failed' / request.name)
                    raise
                request.rename(BASE / 'completed' / request.name)


if __name__ == '__main__':
    main()

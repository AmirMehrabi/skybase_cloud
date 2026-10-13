#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
composer audit --locked --no-interaction
npm audit --audit-level=high
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync MAIL_MAILER=array php artisan test --compact --no-interaction

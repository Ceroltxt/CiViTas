#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")/.."

docker compose exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e DB_URL= \
  -e DB_SCHEMA=public \
  -e DB_SEARCH_PATH=public \
  -e DB_MIGRATIONS_TABLE=migrations \
  -e CACHE_STORE=array \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=array \
  laravel php artisan test "$@"

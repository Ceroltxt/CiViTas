#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")/.."

docker compose exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=postgres \
  -e DB_PORT=5432 \
  -e DB_DATABASE=civitas_testing \
  -e DB_USERNAME=civitas \
  -e DB_PASSWORD=civitas \
  -e DB_URL= \
  -e DB_SCHEMA=public \
  -e DB_SEARCH_PATH="public, laravel" \
  -e DB_MIGRATIONS_TABLE=laravel.migrations \
  -e DB_SSLMODE=disable \
  -e DB_PERSISTENT=false \
  -e CACHE_STORE=array \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=array \
  laravel ./vendor/bin/pest --configuration=phpunit.pgsql.xml "$@"

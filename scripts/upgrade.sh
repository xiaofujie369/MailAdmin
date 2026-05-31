#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/mailadmin-pro}"

valid_path() {
  [[ "$1" =~ ^/[A-Za-z0-9._/@+-]+$ ]]
}

if ! valid_path "${APP_DIR}"; then
  echo "APP_DIR 不合法：${APP_DIR}" >&2
  exit 1
fi

cd "${APP_DIR}"
git pull --ff-only
docker compose build
docker compose run --rm mailadmin-app composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
docker compose run --rm mailadmin-app php artisan migrate --force
docker compose run --rm mailadmin-app php artisan config:cache
docker compose run --rm mailadmin-app php artisan route:cache
docker compose run --rm mailadmin-app php artisan view:cache
docker compose up -d
docker compose restart mailadmin-worker mailadmin-scheduler mailadmin-nginx
echo "MailAdmin Pro 已升级。"

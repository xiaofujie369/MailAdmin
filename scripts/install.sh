#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/mailadmin-pro}"

need_root() {
  if [ "${EUID}" -ne 0 ]; then
    echo "请使用 root 执行安装脚本。" >&2
    exit 1
  fi
}

valid_path() {
  [[ "$1" =~ ^/[A-Za-z0-9._/@+-]+$ ]]
}

need_root
if ! valid_path "${APP_DIR}"; then
  echo "APP_DIR 不合法：${APP_DIR}" >&2
  exit 1
fi

command -v docker >/dev/null || { echo "未安装 Docker。" >&2; exit 1; }
docker compose version >/dev/null || { echo "未安装 Docker Compose v2。" >&2; exit 1; }

cd "${APP_DIR}"

if [ ! -f .env ]; then
  install -m 0600 .env.example .env
  echo "已创建 .env，请修改 ADMIN_PASSWORD、DB_PASSWORD、APP_URL 后再次运行。"
  exit 1
fi
chmod 0600 .env

docker compose build
docker compose up -d mailadmin-mysql mailadmin-redis
sleep 15
docker compose run --rm mailadmin-app composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
docker compose run --rm mailadmin-app php artisan key:generate --force
docker compose run --rm mailadmin-app php artisan migrate --force
docker compose run --rm mailadmin-app php artisan mailadmin:create-admin
docker compose up -d
docker compose run --rm mailadmin-app php artisan config:cache
docker compose run --rm mailadmin-app php artisan route:cache
docker compose run --rm mailadmin-app php artisan view:cache

curl -fsS http://127.0.0.1:8095 >/dev/null
echo "MailAdmin Pro 已安装：http://127.0.0.1:8095"

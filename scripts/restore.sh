#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/mailadmin-pro}"
BACKUP_DIR="${1:-}"

[[ "$APP_DIR" =~ ^/[A-Za-z0-9._/@+-]+$ ]] || { echo "APP_DIR 不合法" >&2; exit 1; }
[[ -n "$BACKUP_DIR" && -d "$BACKUP_DIR" ]] || { echo "用法：scripts/restore.sh /path/to/backup" >&2; exit 1; }

cd "$APP_DIR"
install -m 0600 "${BACKUP_DIR}/.env" .env
set -a
source .env
set +a
tar -xzf "${BACKUP_DIR}/storage.tar.gz"
docker compose up -d mailadmin-mysql mailadmin-redis
sleep 10
docker compose exec -T mailadmin-mysql mysql -u"${DB_USERNAME:-mailadmin}" -p"${DB_PASSWORD:-change-me}" "${DB_DATABASE:-mailadmin}" < "${BACKUP_DIR}/mailadmin.sql"
docker compose up -d
echo "恢复完成。"

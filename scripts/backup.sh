#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/mailadmin-pro}"
BACKUP_DIR="${BACKUP_DIR:-${APP_DIR}/backups/$(date +%Y%m%d-%H%M%S)}"

[[ "$APP_DIR" =~ ^/[A-Za-z0-9._/@+-]+$ ]] || { echo "APP_DIR 不合法" >&2; exit 1; }
mkdir -p "$BACKUP_DIR"
cd "$APP_DIR"
set -a
source .env
set +a

docker compose exec -T mailadmin-mysql mysqldump -u"${DB_USERNAME:-mailadmin}" -p"${DB_PASSWORD:-change-me}" "${DB_DATABASE:-mailadmin}" > "${BACKUP_DIR}/mailadmin.sql"
install -m 0600 .env "${BACKUP_DIR}/.env"
tar -czf "${BACKUP_DIR}/storage.tar.gz" storage
echo "备份完成：${BACKUP_DIR}"

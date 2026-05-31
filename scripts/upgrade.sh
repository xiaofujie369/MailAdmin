#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/mailadmin-pro}"
SERVICE_NAME="mailadmin-pro"

require_root() {
  if [ "${EUID}" -ne 0 ]; then
    echo "Please run as root." >&2
    exit 1
  fi
}

valid_path() {
  [[ "$1" =~ ^/[A-Za-z0-9._/@+-]+$ ]]
}

require_root

if ! valid_path "${APP_DIR}"; then
  echo "Invalid APP_DIR: ${APP_DIR}" >&2
  exit 1
fi

if [ ! -d "${APP_DIR}/.git" ]; then
  echo "${APP_DIR} is not a git checkout." >&2
  exit 1
fi

cd "${APP_DIR}"
git fetch --prune origin
git pull --ff-only

"${APP_DIR}/venv/bin/pip" install --upgrade pip
"${APP_DIR}/venv/bin/pip" install -r requirements.txt

chmod 0600 .env 2>/dev/null || true
install -m 0644 systemd/mailadmin-pro.service "/etc/systemd/system/${SERVICE_NAME}.service"
systemctl daemon-reload
systemctl restart "${SERVICE_NAME}"
systemctl status "${SERVICE_NAME}" --no-pager

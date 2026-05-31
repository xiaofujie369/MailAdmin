#!/usr/bin/env bash
set -e

APP_DIR="/opt/mailadmin-pro"

apt update
apt install -y python3 python3-venv python3-pip curl dnsutils git

cd "$APP_DIR"

python3 -m venv venv
./venv/bin/pip install --upgrade pip
./venv/bin/pip install -r requirements.txt

if [ ! -f .env ]; then
cp example.env .env
echo "请修改 $APP_DIR/.env 里的 ADMIN_PASS"
fi

cp systemd/mailadmin-pro.service /etc/systemd/system/mailadmin-pro.service
systemctl daemon-reload
systemctl enable --now mailadmin-pro

systemctl status mailadmin-pro --no-pager

#!/usr/bin/env bash
set -e

cd /opt/mailadmin-pro
mkdir -p storage/logs

docker logs --since 30m mailserver > storage/logs/mailserver-docker.log 2>&1 || true
chown -R 1000:1000 storage/logs 2>/dev/null || true

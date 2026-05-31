#!/usr/bin/env bash
set -euo pipefail

project_dir="/opt/mailadmin-pro"
container_name="${MAILADMIN_MAILSERVER_CONTAINER:-mailserver}"

if [[ ! -d "$project_dir" ]]; then
  echo "Project directory not found: $project_dir" >&2
  exit 1
fi

if [[ ! "$container_name" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]{0,127}$ ]]; then
  echo "Invalid container name: $container_name" >&2
  exit 1
fi

log_dir="$project_dir/storage/logs"
log_file="$log_dir/mailserver-docker.log"

mkdir -p "$log_dir"
docker logs --since 30m "$container_name" > "$log_file" 2>&1 || true
chown 1000:1000 "$log_dir" "$log_file" 2>/dev/null || true

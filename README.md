# MailAdmin Pro

MailAdmin Pro is a FastAPI dashboard for docker-mailserver operations.

## Features

- Dashboard with today, 7-day, 30-day, daily, weekly, and monthly delivery trends.
- Sent, bounced, deferred, rejected, failure-rate, bounce-rate, and deferred-rate metrics.
- Top recipients, domains, senders, and connection IPs parsed from docker logs.
- Postfix queue viewing with recipient/domain filters, cancel queued mail, retry delivery, and clear queue.
- Blacklist and suppression-list management with audit logs and CSV import/export.
- Rspamd, Postfix, DNS reputation, SPF, DKIM, DMARC, PTR, and queue pressure checks.
- SaaS-style responsive admin UI with dark log windows and confirmation flows.

## Layout

```text
app/
  main.py
  config.py
  auth.py
  database.py
  routers/
  services/
  templates/
  static/
deploy/
scripts/
systemd/
```

## Configuration

Copy `example.env` to `/opt/mailadmin-pro/.env` and set a strong `ADMIN_PASS`.

```bash
ADMIN_USER=admin
ADMIN_PASS=replace-with-a-strong-password
MAIL_CONTAINER=mailserver
```

Do not commit `.env`, SQLite databases, logs, or real passwords.

## Run Locally

```bash
python -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
export ADMIN_PASS=dev-only-password
uvicorn app.main:app --host 127.0.0.1 --port 8095
```

## Deploy

```bash
sudo git clone https://github.com/xiaofujie369/MailAdmin.git /opt/mailadmin-pro
cd /opt/mailadmin-pro
sudo APP_DIR=/opt/mailadmin-pro bash scripts/install.sh
```

Upgrade an existing checkout:

```bash
sudo APP_DIR=/opt/mailadmin-pro bash scripts/upgrade.sh
```

Reverse-proxy examples are in `deploy/Caddyfile.example` and `deploy/nginx.example.conf`.

## Safety

Dangerous queue and list operations require browser confirmation and a server-side confirmation phrase. Shell commands are executed without `shell=True`, and user-controlled values are validated before they are passed to Docker, Postfix, DNS, or database operations.

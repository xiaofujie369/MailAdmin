# MailAdmin Pro

MailAdmin Pro 是面向 `docker-mailserver` 的生产级运维后台。它不是 Webmail，不替代 Roundcube 或 MailNova；它负责邮件投递状态监控、Postfix 队列管理、统计趋势、反垃圾检查、DNS 信誉、名单管理和操作审计。

界面默认语言：中文。

## 技术栈

- PHP 8.3
- Laravel 11
- MySQL 8
- Redis
- Docker Compose
- Nginx
- Laravel Queue Worker
- Laravel Scheduler

旧 FastAPI 版本已保留在 `legacy/fastapi/`，主项目已切换为 Laravel。

## 服务结构

`docker-compose.yml` 包含：

- `mailadmin-app`：Laravel PHP-FPM
- `mailadmin-nginx`：Nginx Web，默认监听 `127.0.0.1:8095`
- `mailadmin-mysql`：MySQL 8
- `mailadmin-redis`：Redis
- `mailadmin-worker`：Laravel queue worker
- `mailadmin-scheduler`：Laravel scheduler

应用会挂载宿主机 Docker Socket：

```yaml
/var/run/docker.sock:/var/run/docker.sock
```

只通过白名单服务执行 Docker/Postfix/Rspamd 命令，禁止拼接用户输入。

## 安装

```bash
sudo git clone https://github.com/xiaofujie369/MailAdmin.git /opt/mailadmin-pro
cd /opt/mailadmin-pro
sudo cp .env.example .env
sudo chmod 600 .env
sudo nano .env
sudo APP_DIR=/opt/mailadmin-pro bash scripts/install.sh
```

请至少修改：

```env
APP_URL=https://mailadmin.example.com
DB_PASSWORD=change-me
MYSQL_ROOT_PASSWORD=change-root-password
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-this-password
MAIL_CONTAINER=mailserver
MAIL_DOMAIN=example.com
MAIL_HOSTNAME=mail.example.com
```

`ADMIN_PASSWORD` 必须至少 12 位，不能使用默认占位值。

## 升级

```bash
cd /opt/mailadmin-pro
sudo APP_DIR=/opt/mailadmin-pro bash scripts/upgrade.sh
```

## 备份与恢复

备份 MySQL、`.env` 和 `storage`：

```bash
sudo APP_DIR=/opt/mailadmin-pro bash scripts/backup.sh
```

恢复：

```bash
sudo APP_DIR=/opt/mailadmin-pro bash scripts/restore.sh /opt/mailadmin-pro/backups/20260101-120000
```

## Caddy 反代

```caddy
mailadmin.example.com {
    encode gzip zstd
    reverse_proxy 127.0.0.1:8095
}
```

## Nginx 反代

见 `deploy/nginx.example.conf`。

## 常用命令

```bash
docker compose ps
docker compose logs -f mailadmin-nginx mailadmin-app
docker compose exec mailadmin-app php artisan mailadmin:collect
docker compose exec mailadmin-app php artisan mailadmin:aggregate
docker compose exec mailadmin-app php artisan mailadmin:queue-snapshot
docker compose exec mailadmin-app php artisan mailadmin:health-check
docker compose exec mailadmin-app php artisan mailadmin:create-admin --email=admin@example.com --password='your-strong-password'
```

## 采集器

Scheduler 每分钟执行：

- `mailadmin:collect`
- `mailadmin:aggregate`
- `mailadmin:queue-snapshot`

每 5 分钟执行：

- `mailadmin:health-check`

首页只读取 MySQL 聚合表，避免打开页面时实时扫描 30 天 Docker 日志。

## 安全说明

- 不提交 `.env`、数据库、日志、真实密码、真实 token。
- 队列危险操作需要 CSRF、浏览器二次确认、确认短语和 audit log。
- `viewer` 只读；`operator` 可查看、重试队列、添加退订；`admin` 拥有全部权限。
- 队列 ID、邮箱、域名、IP、CIDR 都在服务端校验。
- 第一版名单管理不会自动写入 Postfix/Rspamd，后续可增加手动同步。

## 避免 Docker 日志过大

建议在 docker-mailserver 所在宿主机配置 Docker log rotation，例如：

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "100m",
    "max-file": "5"
  }
}
```

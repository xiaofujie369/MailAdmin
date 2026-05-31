# MailAdmin Pro

MailAdmin Pro 是一个面向 `docker-mailserver` 的专业邮件服务器监控后台。

## 当前功能

- 今日 / 7 日 / 30 日发件统计
- 成功、退信、延迟、拒收统计
- 30 日每日趋势
- Top 收件人、Top 收件域名、Top 发件人、Top 连接 IP
- Postfix 邮件队列查看
- 队列邮件取消发送
- 队列重试投递
- 清空队列
- 黑名单管理
- 退订 / 取消接收名单管理
- DNS 信誉检查：MX / SPF / DKIM / DMARC / PTR
- Rspamd / Postfix 反垃圾状态检查
- 系统状态、Docker 状态、操作审计

## 技术栈

- Python 3
- FastAPI
- Uvicorn
- SQLite
- docker-mailserver
- Caddy / Nginx 反向代理

## 默认部署路径

```bash
/opt/mailadmin-pro
默认监听地址
127.0.0.1:8095
启动方式
systemctl restart mailadmin-pro
systemctl status mailadmin-pro --no-pager
环境变量

请创建 .env 文件：

ADMIN_USER=admin
ADMIN_PASS=your-strong-password
MAIL_CONTAINER=mailserver

不要把 .env 上传到 GitHub。

Codex 后续优化方向

请参考 CODEX_TASK.md。

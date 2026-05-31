
Codex Task: Make MailAdmin Pro production-grade

请将当前 MailAdmin Pro 重构成更专业、可长期维护的邮件服务器后台。

项目目标

把当前单文件 FastAPI 项目升级为生产级结构：

app/
  main.py
  config.py
  auth.py
  database.py
  services/
    docker_mailserver.py
    postfix.py
    rspamd.py
    dns_check.py
    stats_parser.py
  routers/
    dashboard.py
    stats.py
    queue.py
    blacklist.py
    antispam.py
    dns.py
    logs.py
    system.py
  templates/
  static/
systemd/
scripts/
deploy/
必须保留的核心功能
1. 发件统计

需要支持：

今日统计
7 日统计
30 日统计
月度统计
成功 sent
退信 bounced
延迟 deferred
拒收 reject
失败率
退信率
延迟率
趋势图
Top 收件人
Top 收件域名
Top 发件人
Top 连接 IP

统计来源优先使用 docker-mailserver 日志：

docker logs --timestamps mailserver
2. 队列管理

支持：

查看 Postfix 队列
单独删除队列邮件
清空全部队列
立即重试队列
按收件人筛选队列
按域名筛选队列

底层命令：

docker exec mailserver postqueue -p
docker exec mailserver postsuper -d MESSAGE_ID
docker exec mailserver postsuper -d ALL
docker exec mailserver postqueue -f

注意：已经 status=sent 的邮件无法撤回，只能取消还在队列中的邮件。

3. 黑名单 / 退订

需要支持：

邮箱黑名单
域名黑名单
IP 黑名单
退订邮箱
退订域名
导出 suppression list
CSV 导入
CSV 导出
操作审计

第一阶段只做后台管理，不要默认直接写入 Postfix，以免误伤正常邮件。

第二阶段再支持手动同步到：

Postfix sender_access
Postfix recipient_access
Postfix client_access
Rspamd multimap
4. 反垃圾

需要检查：

Rspamd 是否运行
Rspamd 配置是否正常
Postfix TLS 配置
Postfix SASL 配置
Milter 配置
DKIM 配置
SPF 配置
DMARC 配置
PTR 反解
队列积压风险
退信率风险
延迟率风险
5. DNS 信誉检查

检查项目：

MX
SPF
DKIM: mail._domainkey
DKIM: default._domainkey
DMARC
mail A 记录
PTR 反向解析
评分 0-100
6. UI 要求

请做成专业 SaaS 后台风格：

左侧菜单
顶部状态栏
卡片式统计
趋势图
表格分页
搜索筛选
操作确认
风险提示
深色日志窗口
移动端适配
7. 安全要求
不要在 GitHub 中提交 .env
不要暴露后台密码
Basic Auth 可以保留，但建议支持 Session 登录
所有危险操作必须二次确认
Shell 命令必须严格过滤参数
队列邮件 ID 只能允许 [A-Fa-f0-9]
域名、邮箱、IP 都要校验
记录 audit log
8. 部署要求

提供：

install.sh
upgrade.sh
systemd/mailadmin-pro.service
example.env
Caddy 反代示例
Nginx 反代示例

默认监听：

127.0.0.1:8095

默认容器名：

mailserver
不要做的事
不要自动删除用户邮件数据
不要默认清空队列
不要默认修改 docker-mailserver 配置
不要把 .env、SQLite 数据库、日志文件提交到仓库
不要把后台密码写死在 README

from . import postfix, rspamd
from .docker_mailserver import docker_logs_since
from .dns_check import check_domain
from .stats_parser import parse_stats


def security_report(domain: str) -> dict[str, object]:
    logs = docker_logs_since(7, 30000)
    stats = parse_stats(logs)
    dns = check_domain(domain)
    queue = postfix.queue_info()
    risks: list[str] = []

    if int(stats["deferred"]) > int(stats["sent"]) * 2 and int(stats["deferred"]) > 20:
        risks.append("延迟队列异常偏高。请检查远端限速、IP 信誉和 DNS 记录。")
    if int(stats["bounced"]) > max(10, int(stats["sent"]) * 0.2):
        risks.append("退信率偏高。请清理无效收件人，并在发送前应用退订名单。")
    if dns["score"] < 80:
        risks.append("DNS 信誉配置不完整。SPF、DKIM、DMARC 和 PTR 应全部通过。")
    if int(queue["count"]) > max(50, int(stats["sent"]) * 0.5):
        risks.append("队列压力偏高。重试投递前请先检查受阻目标。")
    if not risks:
        risks.append("最近邮件日志中未发现明显高风险信号。")

    return {
        "stats": stats,
        "dns": dns,
        "queue": queue,
        "risks": risks,
        "rspamd": rspamd.status(),
        "postfix": postfix.postconf(),
    }

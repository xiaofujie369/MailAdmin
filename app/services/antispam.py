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
        risks.append("Deferred queue is unusually high. Check remote throttling, IP reputation, and DNS records.")
    if int(stats["bounced"]) > max(10, int(stats["sent"]) * 0.2):
        risks.append("Bounce rate is high. Clean invalid recipients and enforce suppression lists before sending.")
    if dns["score"] < 80:
        risks.append("DNS reputation is incomplete. SPF, DKIM, DMARC, and PTR should all pass.")
    if int(queue["count"]) > max(50, int(stats["sent"]) * 0.5):
        risks.append("Queue pressure is high. Review blocked destinations before retrying delivery.")
    if not risks:
        risks.append("No obvious high-risk signal found in recent mail logs.")

    return {
        "stats": stats,
        "dns": dns,
        "queue": queue,
        "risks": risks,
        "rspamd": rspamd.status(),
        "postfix": postfix.postconf(),
    }

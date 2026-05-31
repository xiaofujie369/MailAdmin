import re
from collections import Counter
from datetime import datetime, timedelta, timezone


def parse_ts(line: str) -> datetime | None:
    match = re.match(r"^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})", line)
    if not match:
        return None
    try:
        return datetime.strptime(" ".join(match.groups()), "%Y-%m-%d %H:%M:%S").replace(tzinfo=timezone.utc)
    except ValueError:
        return None


def empty_stats() -> dict[str, int]:
    return {
        "sent": 0,
        "bounced": 0,
        "deferred": 0,
        "reject": 0,
        "warning": 0,
        "connect": 0,
        "auth": 0,
        "spam": 0,
    }


def parse_stats(logs: str) -> dict[str, int | float]:
    stats = empty_stats()
    stats["sent"] = len(re.findall(r"status=sent", logs, re.I))
    stats["bounced"] = len(re.findall(r"status=bounced", logs, re.I))
    stats["deferred"] = len(re.findall(r"status=deferred", logs, re.I))
    stats["reject"] = len(re.findall(r"\bNOQUEUE: reject\b|\breject\b", logs, re.I))
    stats["warning"] = len(re.findall(r"\bwarning\b", logs, re.I))
    stats["connect"] = len(re.findall(r"\bconnect from\b", logs, re.I))
    stats["auth"] = len(re.findall(r"sasl|authentication|auth", logs, re.I))
    stats["spam"] = len(re.findall(r"rspamd|spam|greylist|rbl|blacklist", logs, re.I))
    attempted = max(1, int(stats["sent"]) + int(stats["bounced"]) + int(stats["deferred"]))
    stats["failure_rate"] = round((int(stats["bounced"]) + int(stats["deferred"])) * 100 / attempted, 2)
    stats["bounce_rate"] = round(int(stats["bounced"]) * 100 / attempted, 2)
    stats["defer_rate"] = round(int(stats["deferred"]) * 100 / attempted, 2)
    return stats


def _bucket_for(ts: datetime, granularity: str) -> str:
    if granularity == "week":
        year, week, _ = ts.isocalendar()
        return f"{year}-W{week:02d}"
    if granularity == "month":
        return ts.strftime("%Y-%m")
    return ts.date().isoformat()


def group_trend(logs: str, count: int, granularity: str = "day") -> dict[str, dict[str, int]]:
    now = datetime.now(timezone.utc)
    result: dict[str, dict[str, int]] = {}
    for i in range(count - 1, -1, -1):
        if granularity == "week":
            dt = now - timedelta(weeks=i)
        elif granularity == "month":
            year = now.year
            month = now.month - i
            while month <= 0:
                year -= 1
                month += 12
            dt = now.replace(year=year, month=month, day=1)
        else:
            dt = now - timedelta(days=i)
        result[_bucket_for(dt, granularity)] = empty_stats()

    for line in logs.splitlines():
        ts = parse_ts(line)
        if not ts:
            continue
        key = _bucket_for(ts, granularity)
        if key not in result:
            continue
        if re.search(r"status=sent", line, re.I):
            result[key]["sent"] += 1
        if re.search(r"status=bounced", line, re.I):
            result[key]["bounced"] += 1
        if re.search(r"status=deferred", line, re.I):
            result[key]["deferred"] += 1
        if re.search(r"\bNOQUEUE: reject\b|\breject\b", line, re.I):
            result[key]["reject"] += 1
    return result


def extract_top(logs: str, limit: int = 10) -> dict[str, list[tuple[str, int]]]:
    recipients: Counter[str] = Counter()
    senders: Counter[str] = Counter()
    domains: Counter[str] = Counter()
    ips: Counter[str] = Counter()

    for line in logs.splitlines():
        for match in re.finditer(r"\bto=<([^>]+)>", line, re.I):
            email = match.group(1).lower()
            recipients[email] += 1
            if "@" in email:
                domains[email.rsplit("@", 1)[-1]] += 1
        for match in re.finditer(r"\bfrom=<([^>]+)>", line, re.I):
            email = match.group(1).lower()
            if email:
                senders[email] += 1
        for match in re.finditer(
            r"client=.*?\[(\d+\.\d+\.\d+\.\d+)\]|connect from .*?\[(\d+\.\d+\.\d+\.\d+)\]",
            line,
            re.I,
        ):
            ip = match.group(1) or match.group(2)
            ips[ip] += 1

    return {
        "recipients": recipients.most_common(limit),
        "senders": senders.most_common(limit),
        "domains": domains.most_common(limit),
        "ips": ips.most_common(limit),
    }

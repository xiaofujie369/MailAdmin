import re
from dataclasses import dataclass

from .docker_mailserver import container
from .shell import run_command
from ..validators import clean_domain, clean_email, clean_queue_id


@dataclass(frozen=True)
class QueueMessage:
    id: str
    marker: str
    size: int
    date: str
    sender: str
    recipients: list[str]
    raw: str

    @property
    def domain(self) -> str:
        if "@" not in self.sender:
            return ""
        return self.sender.rsplit("@", 1)[-1]


def parse_queue(raw: str) -> list[QueueMessage]:
    messages: list[QueueMessage] = []
    current: list[str] = []

    def flush() -> None:
        if not current:
            return
        first = current[0]
        match = re.match(r"^([A-Fa-f0-9]{5,64})([*!]?)\s+(\d+)\s+(.+?)\s+(\S+)\s*$", first)
        if not match:
            current.clear()
            return
        message_id, marker, size, date_part, sender = match.groups()
        recipients: list[str] = []
        for line in current[1:]:
            stripped = line.strip()
            if not stripped or stripped.startswith("("):
                continue
            if "@" in stripped:
                recipients.append(stripped.split()[0].strip("<>,"))
        messages.append(
            QueueMessage(
                id=message_id.upper(),
                marker=marker,
                size=int(size),
                date=date_part,
                sender=sender.strip("<>"),
                recipients=recipients,
                raw="\n".join(current),
            )
        )
        current.clear()

    for line in raw.splitlines():
        if re.match(r"^[A-Fa-f0-9]{5,64}[*!]?\s+\d+\s+", line):
            flush()
            current.append(line)
        elif current:
            if line.strip():
                current.append(line)
            else:
                flush()
    flush()
    return messages


def queue_info(recipient: str = "", domain: str = "") -> dict[str, object]:
    output = run_command(["docker", "exec", container(), "postqueue", "-p"], timeout=25).stdout
    messages = parse_queue(output)
    if recipient:
        safe_recipient = clean_email(recipient)
        messages = [m for m in messages if safe_recipient in [r.lower() for r in m.recipients]]
    if domain:
        safe_domain = clean_domain(domain)
        messages = [
            m
            for m in messages
            if m.domain == safe_domain or any(r.lower().endswith(f"@{safe_domain}") for r in m.recipients)
        ]
    return {"count": len(messages), "raw": output, "messages": messages}


def delete_message(message_id: str) -> str:
    safe_id = clean_queue_id(message_id)
    return run_command(["docker", "exec", container(), "postsuper", "-d", safe_id], timeout=25).stdout


def delete_all() -> str:
    return run_command(["docker", "exec", container(), "postsuper", "-d", "ALL"], timeout=35).stdout


def flush_queue() -> str:
    return run_command(["docker", "exec", container(), "postqueue", "-f"], timeout=35).stdout


def postconf() -> str:
    raw = run_command(["docker", "exec", container(), "postconf", "-n"], timeout=25).stdout
    keys = (
        "smtpd_recipient_restrictions",
        "smtpd_sender_restrictions",
        "smtpd_client_restrictions",
        "milter",
        "rspamd",
        "message_size_limit",
        "smtpd_tls_security_level",
        "smtpd_sasl_auth_enable",
        "postscreen",
    )
    return "\n".join(line for line in raw.splitlines() if any(key in line for key in keys)) or raw

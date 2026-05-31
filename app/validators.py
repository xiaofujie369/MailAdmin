import ipaddress
import re


CONTAINER_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9_.-]{0,127}$")
DOMAIN_RE = re.compile(
    r"^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$",
    re.IGNORECASE,
)
EMAIL_RE = re.compile(
    r"^[A-Z0-9._%+-]{1,64}@[A-Z0-9.-]{1,253}\.[A-Z]{2,63}$",
    re.IGNORECASE,
)
QUEUE_ID_RE = re.compile(r"^[A-Fa-f0-9]{5,64}$")
KEYWORD_RE = re.compile(r"^[\w .@:+\-/]{0,80}$", re.UNICODE)


def clean_container(name: str) -> str:
    value = (name or "").strip()
    if not CONTAINER_RE.fullmatch(value):
        raise ValueError("Invalid container name")
    return value


def clean_queue_id(message_id: str) -> str:
    value = (message_id or "").strip()
    if not QUEUE_ID_RE.fullmatch(value):
        raise ValueError("Invalid queue message id")
    return value.upper()


def clean_domain(domain: str) -> str:
    value = (domain or "").strip().lower().rstrip(".")
    if not DOMAIN_RE.fullmatch(value):
        raise ValueError("Invalid domain")
    return value


def clean_email(email: str) -> str:
    value = (email or "").strip().lower()
    if not EMAIL_RE.fullmatch(value):
        raise ValueError("Invalid email address")
    return value


def clean_ip(ip: str) -> str:
    value = (ip or "").strip()
    try:
        return str(ipaddress.ip_address(value))
    except ValueError as exc:
        raise ValueError("Invalid IP address") from exc


def clean_keyword(keyword: str) -> str:
    value = (keyword or "").strip()
    if not KEYWORD_RE.fullmatch(value):
        raise ValueError("Invalid keyword")
    return value


def clean_list_value(kind: str, value: str) -> str:
    if kind == "email":
        return clean_email(value)
    if kind == "domain":
        return clean_domain(value)
    if kind == "ip":
        return clean_ip(value)
    raise ValueError("Invalid list type")


def clean_reason(reason: str) -> str:
    return (reason or "").strip()[:240]


def clean_positive_int(value: int, minimum: int, maximum: int) -> int:
    return max(minimum, min(int(value), maximum))

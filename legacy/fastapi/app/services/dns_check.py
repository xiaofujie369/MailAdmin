from ..validators import clean_domain, clean_ip
from .shell import run_command


def dig(args: list[str]) -> str:
    return run_command(["dig", "+short", *args], timeout=12).stdout


def check_domain(domain: str) -> dict[str, object]:
    safe_domain = clean_domain(domain)
    mx = dig(["MX", safe_domain])
    txt = dig(["TXT", safe_domain])
    spf = "\n".join(line for line in txt.splitlines() if "v=spf1" in line.lower())
    dmarc = dig(["TXT", f"_dmarc.{safe_domain}"])
    dkim_mail = dig(["TXT", f"mail._domainkey.{safe_domain}"])
    dkim_default = dig(["TXT", f"default._domainkey.{safe_domain}"])
    mail_a = dig(["A", f"mail.{safe_domain}"])
    ptr = ""
    first_ip = mail_a.splitlines()[0].strip() if mail_a.strip() else ""
    if first_ip:
        try:
            ptr = dig(["-x", clean_ip(first_ip)])
        except ValueError:
            ptr = ""

    checks = {
        "mx": bool(mx.strip()),
        "spf": "v=spf1" in spf.lower(),
        "dmarc": "v=dmarc1" in dmarc.lower(),
        "dkim": bool(dkim_mail.strip() or dkim_default.strip()),
        "ptr": bool(ptr.strip()),
    }
    score = sum(20 for ok in checks.values() if ok)
    return {
        "domain": safe_domain,
        "score": score,
        "checks": checks,
        "mx": mx,
        "spf": spf,
        "dmarc": dmarc,
        "dkim_mail": dkim_mail,
        "dkim_default": dkim_default,
        "a": mail_a,
        "ptr": ptr,
    }

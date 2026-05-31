from .docker_mailserver import container
from .shell import run_command


def status() -> str:
    pgrep = run_command(["docker", "exec", container(), "pgrep", "-a", "rspamd"], timeout=15).stdout
    configtest = run_command(["docker", "exec", container(), "rspamadm", "configtest"], timeout=20).stdout
    return "\n\n".join([part for part in [pgrep, configtest] if part]) or "Rspamd status unavailable"

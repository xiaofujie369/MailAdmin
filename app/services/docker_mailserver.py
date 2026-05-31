from .shell import run_command
from ..config import settings
from ..validators import clean_container, clean_positive_int


def container() -> str:
    return clean_container(settings.mail_container)


def docker_logs_since(days: int = 1, lines: int = 20000) -> str:
    safe_days = clean_positive_int(days, 1, 365)
    safe_lines = clean_positive_int(lines, 100, 100000)
    result = run_command(
        [
            "docker",
            "logs",
            "--timestamps",
            "--since",
            f"{safe_days * 24}h",
            "--tail",
            str(safe_lines),
            container(),
        ],
        timeout=45,
    )
    return result.stdout


def docker_logs_tail(lines: int = 800) -> str:
    safe_lines = clean_positive_int(lines, 100, 10000)
    result = run_command(
        ["docker", "logs", "--timestamps", "--tail", str(safe_lines), container()],
        timeout=25,
    )
    return result.stdout


def container_status() -> str:
    return run_command(
        [
            "docker",
            "ps",
            "--filter",
            f"name={container()}",
            "--format",
            "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}",
        ],
        timeout=15,
    ).stdout


def system_status() -> dict[str, str]:
    docker_stats = run_command(
        [
            "docker",
            "stats",
            "--no-stream",
            "--format",
            "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}",
        ],
        timeout=20,
    ).stdout
    return {
        "load": run_command(["uptime"], timeout=10).stdout,
        "disk": run_command(["df", "-h", "/", "/var/lib/docker"], timeout=10).stdout
        or run_command(["df", "-h", "/"], timeout=10).stdout,
        "mem": run_command(["free", "-h"], timeout=10).stdout,
        "docker": docker_stats,
    }

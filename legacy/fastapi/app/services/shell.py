import subprocess
from dataclasses import dataclass


@dataclass(frozen=True)
class CommandResult:
    stdout: str
    returncode: int

    @property
    def ok(self) -> bool:
        return self.returncode == 0


def run_command(args: list[str], timeout: int = 25) -> CommandResult:
    if not args or any(not isinstance(part, str) or not part for part in args):
        raise ValueError("命令参数必须是非空字符串")
    try:
        proc = subprocess.run(
            args,
            shell=False,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=timeout,
            check=False,
        )
        return CommandResult(proc.stdout.strip(), proc.returncode)
    except subprocess.TimeoutExpired:
        return CommandResult("Command timeout", 124)
    except FileNotFoundError as exc:
        return CommandResult(str(exc), 127)
    except Exception as exc:
        return CommandResult(str(exc), 1)

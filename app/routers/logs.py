from fastapi import APIRouter, Depends, Request

from .common import context, templates
from ..auth import require_auth
from ..services.docker_mailserver import docker_logs_tail
from ..validators import clean_keyword, clean_positive_int


router = APIRouter(prefix="/logs", tags=["logs"])


@router.get("")
def logs_page(
    request: Request,
    lines: int = 1000,
    keyword: str = "",
    _: bool = Depends(require_auth),
):
    safe_lines = clean_positive_int(lines, 100, 10000)
    safe_keyword = clean_keyword(keyword)
    data = docker_logs_tail(safe_lines)
    if safe_keyword:
        needle = safe_keyword.lower()
        data = "\n".join(line for line in data.splitlines() if needle in line.lower())
    ctx = context(request, "Logs", "logs")
    ctx.update({"logs": data, "lines": safe_lines, "keyword": safe_keyword})
    return templates.TemplateResponse("logs.html", ctx)

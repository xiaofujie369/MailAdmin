from fastapi import APIRouter, Depends, Request

from .common import context, templates
from ..auth import require_auth
from ..services.docker_mailserver import container_status, docker_logs_since
from ..services.postfix import queue_info
from ..services.stats_parser import extract_top, group_trend, parse_stats


router = APIRouter()


@router.get("/")
def index(request: Request, _: bool = Depends(require_auth)):
    logs_1 = docker_logs_since(1)
    logs_7 = docker_logs_since(7)
    logs_30 = docker_logs_since(30, 50000)
    today = parse_stats(logs_1)
    seven = parse_stats(logs_7)
    thirty = parse_stats(logs_30)
    trend = group_trend(logs_30, 14, "day")
    top = extract_top(logs_30, 5)
    queue = queue_info()
    ctx = context(request, "仪表盘", "dashboard")
    ctx.update(
        {
            "today": today,
            "seven": seven,
            "thirty": thirty,
            "trend": trend,
            "top": top,
            "queue_count": queue["count"],
            "container_status": container_status(),
        }
    )
    return templates.TemplateResponse(request, "dashboard.html", ctx)

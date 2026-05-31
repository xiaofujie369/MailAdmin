import json

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse

from .common import context, templates
from ..auth import require_auth
from ..services.docker_mailserver import docker_logs_since
from ..services.postfix import queue_info
from ..services.stats_parser import extract_top, group_trend, parse_stats


router = APIRouter(prefix="/stats", tags=["stats"])
legacy_router = APIRouter(prefix="/api", tags=["stats"])


@router.get("")
def stats_page(request: Request, _: bool = Depends(require_auth)):
    logs_30 = docker_logs_since(30, 80000)
    logs_90 = docker_logs_since(90, 120000)
    logs_365 = docker_logs_since(365, 200000)
    daily = group_trend(logs_30, 30, "day")
    weekly = group_trend(logs_90, 12, "week")
    monthly = group_trend(logs_365, 12, "month")
    top = extract_top(logs_30)
    ctx = context(request, "统计分析", "stats")
    ctx.update(
        {
            "summary": parse_stats(logs_30),
            "daily": daily,
            "weekly": weekly,
            "monthly": monthly,
            "top": top,
            "chart_data": json.dumps({"daily": daily, "weekly": weekly, "monthly": monthly}, ensure_ascii=False),
        }
    )
    return templates.TemplateResponse("stats.html", ctx)


@router.get("/api")
def stats_api(_: bool = Depends(require_auth)):
    logs_1 = docker_logs_since(1)
    logs_7 = docker_logs_since(7)
    logs_30 = docker_logs_since(30, 80000)
    queue = queue_info()
    return JSONResponse(
        {
            "today": parse_stats(logs_1),
            "seven_days": parse_stats(logs_7),
            "thirty_days": parse_stats(logs_30),
            "daily": group_trend(logs_30, 30, "day"),
            "queue": queue["count"],
        }
    )


@legacy_router.get("/stats")
def legacy_stats_api(_: bool = Depends(require_auth)):
    return stats_api(_)

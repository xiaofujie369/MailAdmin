from fastapi import APIRouter, Depends, Request

from .common import context, templates
from ..auth import require_auth
from ..database import list_rows
from ..services.docker_mailserver import system_status


router = APIRouter(prefix="/system", tags=["system"])


@router.get("")
def system_page(request: Request, _: bool = Depends(require_auth)):
    ctx = context(request, "系统", "system")
    ctx.update({"status": system_status(), "audit_rows": list_rows("audit_log")[:100]})
    return templates.TemplateResponse(request, "system.html", ctx)

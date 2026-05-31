from fastapi import APIRouter, Depends, Request

from .common import context, templates
from ..auth import require_auth
from ..services.dns_check import check_domain


router = APIRouter(prefix="/dns", tags=["dns"])


@router.get("")
def dns_page(request: Request, domain: str = "example.com", _: bool = Depends(require_auth)):
    result = check_domain(domain)
    ctx = context(request, "DNS 信誉检查", "dns")
    ctx.update({"dns": result})
    return templates.TemplateResponse("dns.html", ctx)

from fastapi import APIRouter, Depends, Request

from .common import context, templates
from ..auth import require_auth
from ..services.antispam import security_report


router = APIRouter(prefix="/antispam", tags=["antispam"])


@router.get("")
def antispam_page(request: Request, domain: str = "example.com", _: bool = Depends(require_auth)):
    report = security_report(domain)
    ctx = context(request, "反垃圾", "antispam")
    ctx.update({"report": report, "domain": report["dns"]["domain"]})
    return templates.TemplateResponse(request, "antispam.html", ctx)

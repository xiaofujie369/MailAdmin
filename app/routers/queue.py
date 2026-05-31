from fastapi import APIRouter, Depends, Form, Request
from fastapi.responses import RedirectResponse

from .common import context, require_confirmation, templates
from ..auth import require_auth
from ..database import audit
from ..services import postfix
from ..validators import clean_domain, clean_email, clean_queue_id


router = APIRouter(prefix="/queue", tags=["queue"])


@router.get("")
def queue_page(
    request: Request,
    recipient: str = "",
    domain: str = "",
    _: bool = Depends(require_auth),
):
    clean_recipient = clean_email(recipient) if recipient else ""
    clean_filter_domain = clean_domain(domain) if domain else ""
    queue = postfix.queue_info(clean_recipient, clean_filter_domain)
    ctx = context(request, "Queue", "queue")
    ctx.update({"queue": queue, "recipient": clean_recipient, "domain": clean_filter_domain})
    return templates.TemplateResponse("queue.html", ctx)


@router.post("/cancel")
def cancel_message(
    _: bool = Depends(require_auth),
    message_id: str = Form(...),
    confirm: str = Form(...),
):
    require_confirmation(confirm, "DELETE")
    safe_id = clean_queue_id(message_id)
    output = postfix.delete_message(safe_id)
    audit("queue_cancel", f"{safe_id}: {output}")
    return RedirectResponse("/queue", status_code=303)


@router.post("/delete-one")
def legacy_delete_one(
    _: bool = Depends(require_auth),
    message_id: str = Form(...),
    confirm: str = Form(...),
):
    return cancel_message(_, message_id, confirm)


@router.post("/delete-all")
def delete_all(_: bool = Depends(require_auth), confirm: str = Form(...)):
    require_confirmation(confirm, "DELETE ALL")
    output = postfix.delete_all()
    audit("queue_delete_all", output)
    return RedirectResponse("/queue", status_code=303)


@router.post("/retry")
def retry_queue(_: bool = Depends(require_auth), confirm: str = Form(...)):
    require_confirmation(confirm, "RETRY")
    output = postfix.flush_queue()
    audit("queue_retry", output)
    return RedirectResponse("/queue", status_code=303)


@router.post("/flush")
def legacy_flush(_: bool = Depends(require_auth), confirm: str = Form(...)):
    return retry_queue(_, confirm)

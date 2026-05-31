from fastapi import APIRouter, Depends, File, Form, Request, UploadFile
from fastapi.responses import PlainTextResponse, RedirectResponse

from .common import context, require_confirmation, templates
from ..auth import require_auth
from ..database import (
    add_blocklist,
    add_suppression,
    audit,
    delete_row,
    list_rows,
    parse_csv_bytes,
    rows_to_csv,
)
from ..validators import clean_list_value, clean_reason


router = APIRouter(prefix="/blacklist", tags=["blacklist"])
legacy_router = APIRouter(tags=["blacklist"])


@router.get("")
def blacklist_page(request: Request, _: bool = Depends(require_auth)):
    ctx = context(request, "名单管理", "blacklist")
    ctx.update({"blocklist": list_rows("blocklist"), "suppressions": list_rows("suppressions")})
    return templates.TemplateResponse("blacklist.html", ctx)


@router.post("/add")
def add_blacklist(
    _: bool = Depends(require_auth),
    type: str = Form(...),
    value: str = Form(...),
    action: str = Form("REJECT"),
    reason: str = Form(""),
):
    kind = type.strip().lower()
    safe_value = clean_list_value(kind, value)
    safe_action = action.strip().upper()
    if safe_action not in {"REJECT", "DISCARD", "HOLD"}:
        safe_action = "REJECT"
    safe_reason = clean_reason(reason)
    add_blocklist(kind, safe_value, safe_action, safe_reason)
    audit("blacklist_add", f"{kind} {safe_value} {safe_action} {safe_reason}")
    return RedirectResponse("/blacklist", status_code=303)


@router.post("/suppressions/add")
def add_suppression_route(
    _: bool = Depends(require_auth),
    type: str = Form(...),
    value: str = Form(...),
    reason: str = Form(""),
):
    kind = type.strip().lower()
    if kind not in {"email", "domain"}:
        kind = "email"
    safe_value = clean_list_value(kind, value)
    safe_reason = clean_reason(reason)
    add_suppression(kind, safe_value, safe_reason)
    audit("suppression_add", f"{kind} {safe_value} {safe_reason}")
    return RedirectResponse("/blacklist", status_code=303)


@router.post("/delete")
def delete_record(
    _: bool = Depends(require_auth),
    table: str = Form(...),
    row_id: int = Form(...),
    confirm: str = Form(...),
):
    require_confirmation(confirm, "DELETE")
    safe_table = table if table in {"blocklist", "suppressions"} else "suppressions"
    delete_row(safe_table, row_id)
    audit("list_delete", f"{safe_table} id={row_id}")
    return RedirectResponse("/blacklist", status_code=303)


@router.post("/import")
async def import_csv(
    _: bool = Depends(require_auth),
    table: str = Form(...),
    confirm: str = Form(...),
    file: UploadFile = File(...),
):
    require_confirmation(confirm, "IMPORT")
    safe_table = table if table in {"blocklist", "suppressions"} else "suppressions"
    imported = 0
    for row in parse_csv_bytes(await file.read()):
        kind = (row.get("type") or "email").strip().lower()
        if safe_table == "suppressions" and kind not in {"email", "domain"}:
            kind = "email"
        value = clean_list_value(kind, row.get("value", ""))
        reason = clean_reason(row.get("reason", ""))
        if safe_table == "blocklist":
            action = (row.get("action") or "REJECT").strip().upper()
            if action not in {"REJECT", "DISCARD", "HOLD"}:
                action = "REJECT"
            add_blocklist(kind, value, action, reason)
        else:
            add_suppression(kind, value, reason)
        imported += 1
    audit("csv_import", f"{safe_table} rows={imported}")
    return RedirectResponse("/blacklist", status_code=303)


@router.get("/export/{table}", response_class=PlainTextResponse)
def export_csv(table: str, _: bool = Depends(require_auth)):
    safe_table = table if table in {"blocklist", "suppressions"} else "suppressions"
    return PlainTextResponse(
        rows_to_csv(list_rows(safe_table), safe_table),
        media_type="text/csv; charset=utf-8",
        headers={"Content-Disposition": f'attachment; filename="{safe_table}.csv"'},
    )


@legacy_router.get("/export/{table}", response_class=PlainTextResponse)
def legacy_export_csv(table: str, _: bool = Depends(require_auth)):
    return export_csv(table, _)

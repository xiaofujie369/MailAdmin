from pathlib import Path

from fastapi import HTTPException, Request
from fastapi.templating import Jinja2Templates

from ..config import settings


templates = Jinja2Templates(directory=Path(__file__).resolve().parents[1] / "templates")


def context(request: Request, title: str, active: str) -> dict[str, object]:
    return {
        "request": request,
        "title": title,
        "active": active,
        "settings": settings,
    }


def require_confirmation(actual: str, expected: str) -> None:
    if actual != expected:
        raise HTTPException(status_code=400, detail=f"请输入 {expected} 以确认该操作")

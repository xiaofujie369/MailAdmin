from pathlib import Path

from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

from .config import settings
from .database import init_db
from .routers import antispam, blacklist, dashboard, dns, logs, queue, stats, system


def create_app() -> FastAPI:
    app = FastAPI(title=settings.app_name)
    base_dir = Path(__file__).resolve().parent
    app.mount("/static", StaticFiles(directory=base_dir / "static"), name="static")
    init_db()
    app.include_router(dashboard.router)
    app.include_router(stats.router)
    app.include_router(stats.legacy_router)
    app.include_router(queue.router)
    app.include_router(blacklist.router)
    app.include_router(blacklist.legacy_router)
    app.include_router(antispam.router)
    app.include_router(dns.router)
    app.include_router(logs.router)
    app.include_router(system.router)
    return app


app = create_app()

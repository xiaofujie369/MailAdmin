import csv
import io
import sqlite3
from datetime import datetime, timezone
from pathlib import Path
from typing import Iterable

from .config import settings


def utc_now() -> str:
    return datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S UTC")


def get_db() -> sqlite3.Connection:
    settings.app_dir.mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(settings.db_path)
    conn.row_factory = sqlite3.Row
    return conn


def init_db() -> None:
    with get_db() as conn:
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS suppressions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                value TEXT NOT NULL UNIQUE,
                reason TEXT,
                created_at TEXT NOT NULL
            )
            """
        )
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS blocklist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                value TEXT NOT NULL UNIQUE,
                action TEXT NOT NULL DEFAULT 'REJECT',
                reason TEXT,
                created_at TEXT NOT NULL
            )
            """
        )
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                detail TEXT,
                created_at TEXT NOT NULL
            )
            """
        )


def audit(action: str, detail: str = "") -> None:
    with get_db() as conn:
        conn.execute(
            "INSERT INTO audit_log(action, detail, created_at) VALUES (?, ?, ?)",
            (action[:80], detail[:1000], utc_now()),
        )
        conn.execute(
            "DELETE FROM audit_log WHERE id NOT IN (SELECT id FROM audit_log ORDER BY id DESC LIMIT ?)",
            (settings.audit_limit,),
        )


def list_rows(table: str) -> list[sqlite3.Row]:
    if table not in {"blocklist", "suppressions", "audit_log"}:
        raise ValueError("Invalid table")
    with get_db() as conn:
        return conn.execute(f"SELECT * FROM {table} ORDER BY id DESC").fetchall()


def delete_row(table: str, row_id: int) -> None:
    if table not in {"blocklist", "suppressions"}:
        raise ValueError("Invalid table")
    with get_db() as conn:
        conn.execute(f"DELETE FROM {table} WHERE id=?", (int(row_id),))


def add_blocklist(kind: str, value: str, action: str, reason: str) -> None:
    with get_db() as conn:
        conn.execute(
            """
            INSERT OR IGNORE INTO blocklist(type, value, action, reason, created_at)
            VALUES (?, ?, ?, ?, ?)
            """,
            (kind, value, action, reason, utc_now()),
        )


def add_suppression(kind: str, value: str, reason: str) -> None:
    with get_db() as conn:
        conn.execute(
            """
            INSERT OR IGNORE INTO suppressions(type, value, reason, created_at)
            VALUES (?, ?, ?, ?)
            """,
            (kind, value, reason, utc_now()),
        )


def rows_to_csv(rows: Iterable[sqlite3.Row], table: str) -> str:
    out = io.StringIO()
    writer = csv.writer(out)
    if table == "blocklist":
        writer.writerow(["type", "value", "action", "reason", "created_at"])
        for row in rows:
            writer.writerow([row["type"], row["value"], row["action"], row["reason"] or "", row["created_at"]])
    elif table == "suppressions":
        writer.writerow(["type", "value", "reason", "created_at"])
        for row in rows:
            writer.writerow([row["type"], row["value"], row["reason"] or "", row["created_at"]])
    else:
        raise ValueError("Invalid CSV table")
    return out.getvalue()


def parse_csv_bytes(data: bytes) -> list[dict[str, str]]:
    text = data.decode("utf-8-sig")
    reader = csv.DictReader(io.StringIO(text))
    return [{k.strip(): (v or "").strip() for k, v in row.items() if k} for row in reader]


def ensure_private_file(path: Path) -> None:
    if path.exists():
        path.chmod(0o600)

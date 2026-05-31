import os
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class Settings:
    app_name: str = "MailAdmin Pro"
    app_dir: Path = Path(os.getenv("APP_DIR", "/opt/mailadmin-pro"))
    mail_container: str = os.getenv("MAIL_CONTAINER", "mailserver")
    admin_user: str = os.getenv("ADMIN_USER", "admin")
    admin_pass: str = os.getenv("ADMIN_PASS", "")
    host: str = os.getenv("HOST", "127.0.0.1")
    port: int = int(os.getenv("PORT", "8095"))
    default_language: str = os.getenv("DEFAULT_LANGUAGE", "zh-CN")

    @property
    def db_path(self) -> Path:
        return self.app_dir / "mailadmin.db"

    @property
    def audit_limit(self) -> int:
        return int(os.getenv("AUDIT_LIMIT", "5000"))

    @property
    def password_configured(self) -> bool:
        return bool(self.admin_pass and self.admin_pass != "change-this-password")


settings = Settings()

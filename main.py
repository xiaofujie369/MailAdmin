import os
import re
import json
import sqlite3
import secrets
import subprocess
from datetime import datetime, timedelta
from pathlib import Path
from fastapi import FastAPI, Request, Depends, HTTPException, Form
from fastapi.responses import HTMLResponse, RedirectResponse, PlainTextResponse
from fastapi.security import HTTPBasic, HTTPBasicCredentials
from starlette.status import HTTP_401_UNAUTHORIZED

APP_NAME = "MailAdmin Pro v3"
APP_DIR = Path("/opt/mailadmin-pro")
DB_PATH = APP_DIR / "mailadmin.db"

MAIL_CONTAINER = os.getenv("MAIL_CONTAINER", "mailserver")
ADMIN_USER = os.getenv("ADMIN_USER", "admin")
ADMIN_PASS = os.getenv("ADMIN_PASS", "KoyunMailAdmin_2026")

app = FastAPI(title=APP_NAME)
security = HTTPBasic()


def auth(credentials: HTTPBasicCredentials = Depends(security)):
    ok_user = secrets.compare_digest(credentials.username, ADMIN_USER)
    ok_pass = secrets.compare_digest(credentials.password, ADMIN_PASS)
    if not (ok_user and ok_pass):
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail="Unauthorized",
            headers={"WWW-Authenticate": "Basic"},
        )
    return True


def run(cmd, timeout=25):
    try:
        p = subprocess.run(
            cmd,
            shell=True,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=timeout,
        )
        return p.stdout.strip()
    except subprocess.TimeoutExpired:
        return "Command timeout"
    except Exception as e:
        return str(e)


def db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


def init_db():
    conn = db()
    conn.execute("""
    CREATE TABLE IF NOT EXISTS suppressions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL,
        value TEXT NOT NULL UNIQUE,
        reason TEXT,
        created_at TEXT NOT NULL
    )
    """)
    conn.execute("""
    CREATE TABLE IF NOT EXISTS blocklist (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL,
        value TEXT NOT NULL UNIQUE,
        action TEXT NOT NULL DEFAULT 'REJECT',
        reason TEXT,
        created_at TEXT NOT NULL
    )
    """)
    conn.execute("""
    CREATE TABLE IF NOT EXISTS audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        action TEXT NOT NULL,
        detail TEXT,
        created_at TEXT NOT NULL
    )
    """)
    conn.commit()
    conn.close()


init_db()


def audit(action, detail=""):
    conn = db()
    conn.execute(
        "INSERT INTO audit_log(action, detail, created_at) VALUES (?, ?, ?)",
        (action, detail, datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")),
    )
    conn.commit()
    conn.close()


def docker_logs_since(days=1, lines=20000):
    since = f"{int(days * 24)}h"
    return run(
        f"docker logs --timestamps --since {since} --tail {int(lines)} {MAIL_CONTAINER} 2>&1",
        timeout=35,
    )


def docker_logs_tail(lines=800):
    return run(
        f"docker logs --timestamps --tail {int(lines)} {MAIL_CONTAINER} 2>&1",
        timeout=25,
    )


def parse_ts(line):
    m = re.match(r"^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})", line)
    if not m:
        return None
    try:
        return datetime.strptime(m.group(1) + " " + m.group(2), "%Y-%m-%d %H:%M:%S")
    except Exception:
        return None


def empty_stats():
    return {
        "sent": 0,
        "bounced": 0,
        "deferred": 0,
        "reject": 0,
        "warning": 0,
        "connect": 0,
        "auth": 0,
        "spam": 0,
    }


def parse_stats(logs):
    s = empty_stats()

    s["sent"] = len(re.findall(r"status=sent", logs, re.I))
    s["bounced"] = len(re.findall(r"status=bounced", logs, re.I))
    s["deferred"] = len(re.findall(r"status=deferred", logs, re.I))
    s["reject"] = len(re.findall(r"\bNOQUEUE: reject\b|\breject\b", logs, re.I))
    s["warning"] = len(re.findall(r"\bwarning\b", logs, re.I))
    s["connect"] = len(re.findall(r"\bconnect from\b", logs, re.I))
    s["auth"] = len(re.findall(r"sasl|authentication|auth", logs, re.I))
    s["spam"] = len(re.findall(r"rspamd|spam|greylist|rbl|blacklist", logs, re.I))

    return s


def group_daily(logs, days=30):
    today = datetime.utcnow().date()
    result = {}
    for i in range(days - 1, -1, -1):
        d = today - timedelta(days=i)
        result[d.isoformat()] = empty_stats()

    for line in logs.splitlines():
        ts = parse_ts(line)
        if not ts:
            continue
        key = ts.date().isoformat()
        if key not in result:
            continue

        if re.search(r"status=sent", line, re.I):
            result[key]["sent"] += 1
        if re.search(r"status=bounced", line, re.I):
            result[key]["bounced"] += 1
        if re.search(r"status=deferred", line, re.I):
            result[key]["deferred"] += 1
        if re.search(r"\bNOQUEUE: reject\b|\breject\b", line, re.I):
            result[key]["reject"] += 1

    return result


def extract_top(logs):
    recipients = {}
    senders = {}
    domains = {}
    ips = {}

    for line in logs.splitlines():
        for m in re.finditer(r"\bto=<([^>]+)>", line, re.I):
            email = m.group(1).lower()
            recipients[email] = recipients.get(email, 0) + 1
            if "@" in email:
                domain = email.split("@")[-1]
                domains[domain] = domains.get(domain, 0) + 1

        for m in re.finditer(r"\bfrom=<([^>]+)>", line, re.I):
            email = m.group(1).lower()
            if email:
                senders[email] = senders.get(email, 0) + 1

        for m in re.finditer(r"client=.*?\[(\d+\.\d+\.\d+\.\d+)\]|connect from .*?\[(\d+\.\d+\.\d+\.\d+)\]", line, re.I):
            ip = m.group(1) or m.group(2)
            ips[ip] = ips.get(ip, 0) + 1

    def top(d, n=10):
        return sorted(d.items(), key=lambda x: x[1], reverse=True)[:n]

    return {
        "recipients": top(recipients),
        "senders": top(senders),
        "domains": top(domains),
        "ips": top(ips),
    }


def queue_info():
    q = run(f"docker exec {MAIL_CONTAINER} postqueue -p 2>&1", timeout=20)
    if "Mail queue is empty" in q:
        count = 0
    else:
        count = len(re.findall(r"^[A-F0-9]{5,}", q, re.M))

    ids = re.findall(r"^([A-F0-9]{5,})[*!]?", q, re.M)
    return count, q, ids


def container_status():
    return run(
        f'docker ps --filter "name={MAIL_CONTAINER}" --format "table {{{{.Names}}}}\\t{{{{.Image}}}}\\t{{{{.Status}}}}\\t{{{{.Ports}}}}"'
    )


def system_status():
    return {
        "load": run("uptime"),
        "disk": run("df -h / /var/lib/docker 2>/dev/null || df -h /"),
        "mem": run("free -h"),
        "docker": run("docker stats --no-stream --format 'table {{.Name}}\\t{{.CPUPerc}}\\t{{.MemUsage}}\\t{{.NetIO}}\\t{{.BlockIO}}'"),
    }


def dns_check(domain):
    safe = re.sub(r"[^a-zA-Z0-9._-]", "", domain)
    mx = run(f"dig +short MX {safe}")
    spf = run(f"dig +short TXT {safe} | grep -i spf || true")
    dmarc = run(f"dig +short TXT _dmarc.{safe}")
    dkim_mail = run(f"dig +short TXT mail._domainkey.{safe} || true")
    dkim_default = run(f"dig +short TXT default._domainkey.{safe} || true")
    a = run(f"dig +short A mail.{safe}")
    ptr = ""
    first_ip = a.splitlines()[0].strip() if a.strip() else ""
    if first_ip:
        ptr = run(f"dig +short -x {first_ip}")

    score = 0
    if mx.strip():
        score += 20
    if "v=spf1" in spf.lower():
        score += 20
    if "v=dmarc1" in dmarc.lower():
        score += 20
    if dkim_mail.strip() or dkim_default.strip():
        score += 20
    if ptr.strip():
        score += 20

    return {
        "domain": safe,
        "score": score,
        "mx": mx,
        "spf": spf,
        "dmarc": dmarc,
        "dkim_mail": dkim_mail,
        "dkim_default": dkim_default,
        "a": a,
        "ptr": ptr,
    }


def rspamd_status():
    out = run(f"docker exec {MAIL_CONTAINER} sh -lc 'pgrep -a rspamd || true; rspamadm configtest 2>&1 || true' 2>&1", timeout=20)
    return out


def postfix_security_status():
    checks = {
        "postconf": run(f"docker exec {MAIL_CONTAINER} postconf -n 2>&1 | egrep 'smtpd_recipient_restrictions|smtpd_sender_restrictions|smtpd_client_restrictions|milter|rspamd|message_size_limit|smtpd_tls_security_level|smtpd_sasl_auth_enable|postscreen' || true", timeout=20),
        "mailq": queue_info()[1],
        "rspamd": rspamd_status(),
    }
    return checks


def list_table(table):
    conn = db()
    rows = conn.execute(f"SELECT * FROM {table} ORDER BY id DESC").fetchall()
    conn.close()
    return rows


def render_rows(rows):
    if not rows:
        return "<p class='muted'>暂无记录</p>"
    html = "<table><tr><th>ID</th><th>类型</th><th>值</th><th>动作</th><th>原因</th><th>时间</th><th>操作</th></tr>"
    for r in rows:
        action = r["action"] if "action" in r.keys() else "-"
        html += f"""
<tr>
<td>{r['id']}</td>
<td>{escape(r['type'])}</td>
<td><code>{escape(r['value'])}</code></td>
<td>{escape(action)}</td>
<td>{escape(r['reason'] or '')}</td>
<td>{escape(r['created_at'])}</td>
<td>
<form method="post" action="/delete-record" style="display:inline">
<input type="hidden" name="table" value="{'blocklist' if 'action' in r.keys() else 'suppressions'}">
<input type="hidden" name="id" value="{r['id']}">
<button class="btn-danger smallbtn" onclick="return confirm('确认删除？')">删除</button>
</form>
</td>
</tr>
"""
    html += "</table>"
    return html


def escape(x):
    return str(x).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


def stat_card(label, value, cls=""):
    return f"<div class='card'><div class='k'>{label}</div><div class='v {cls}'>{value}</div></div>"


def nav():
    return """
<div class="nav">
  <a href="/">仪表盘</a>
  <a href="/stats">统计分析</a>
  <a href="/queue">队列/取消</a>
  <a href="/blacklist">黑名单/退订</a>
  <a href="/antispam">反垃圾</a>
  <a href="/dns">DNS 检查</a>
  <a href="/logs">日志</a>
  <a href="/system">系统</a>
</div>
"""


def layout(title, body):
    return f"""
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{escape(title)}</title>
<style>
:root {{
  --bg:#f4f7fb;
  --card:#ffffff;
  --text:#172033;
  --muted:#64748b;
  --line:#e5e7eb;
  --blue:#2563eb;
  --green:#16a34a;
  --red:#dc2626;
  --orange:#d97706;
  --dark:#0f172a;
}}
* {{ box-sizing:border-box; }}
body {{
  margin:0;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,"Noto Sans SC",sans-serif;
  background:var(--bg);
  color:var(--text);
}}
.header {{
  background:linear-gradient(135deg,#0f172a,#111827);
  color:white;
  padding:24px 32px;
}}
.header h1 {{ margin:0; font-size:25px; }}
.header p {{ margin:8px 0 0; color:#cbd5e1; }}
.nav {{
  background:white;
  padding:13px 32px;
  border-bottom:1px solid var(--line);
  position:sticky;
  top:0;
  z-index:10;
}}
.nav a {{
  display:inline-block;
  margin-right:18px;
  color:var(--blue);
  text-decoration:none;
  font-weight:700;
}}
.wrap {{ padding:26px 32px; }}
.grid {{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(185px,1fr));
  gap:16px;
  margin-bottom:22px;
}}
.grid2 {{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:16px;
  margin-bottom:22px;
}}
.card {{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:18px;
  padding:20px;
  box-shadow:0 8px 24px rgba(15,23,42,.06);
}}
.k {{ color:var(--muted); font-size:13px; }}
.v {{ font-size:32px; font-weight:900; margin-top:6px; letter-spacing:.5px; }}
.ok {{ color:var(--green); }}
.bad {{ color:var(--red); }}
.warn {{ color:var(--orange); }}
.muted {{ color:var(--muted); }}
pre {{
  background:#0b1020;
  color:#d1e7ff;
  padding:18px;
  border-radius:14px;
  overflow:auto;
  line-height:1.45;
  font-size:13px;
}}
table {{
  width:100%;
  border-collapse:collapse;
  background:white;
}}
th,td {{
  padding:11px 10px;
  border-bottom:1px solid var(--line);
  text-align:left;
  vertical-align:top;
}}
th {{
  color:#475569;
  font-size:13px;
  background:#f8fafc;
}}
input,select {{
  padding:10px 12px;
  border:1px solid #cbd5e1;
  border-radius:10px;
  min-width:220px;
  background:white;
}}
button,.btn {{
  padding:10px 15px;
  border:0;
  background:var(--blue);
  color:white;
  border-radius:10px;
  font-weight:800;
  cursor:pointer;
}}
.btn-danger {{ background:var(--red); }}
.btn-warn {{ background:var(--orange); }}
.smallbtn {{ padding:6px 10px; font-size:12px; }}
.bar {{
  height:11px;
  background:#e2e8f0;
  border-radius:999px;
  overflow:hidden;
}}
.bar span {{
  display:block;
  height:100%;
  background:linear-gradient(90deg,#2563eb,#16a34a);
}}
.badbar span {{
  background:linear-gradient(90deg,#f97316,#dc2626);
}}
code {{
  background:#f1f5f9;
  padding:2px 6px;
  border-radius:6px;
}}
.notice {{
  padding:14px 16px;
  background:#eff6ff;
  border:1px solid #bfdbfe;
  color:#1e3a8a;
  border-radius:14px;
  margin-bottom:16px;
}}
.dangerbox {{
  padding:14px 16px;
  background:#fef2f2;
  border:1px solid #fecaca;
  color:#991b1b;
  border-radius:14px;
  margin-bottom:16px;
}}
</style>
</head>
<body>
<div class="header">
  <h1>{APP_NAME}</h1>
  <p>专业邮件服务器监控 / 发件统计 / 队列取消 / 黑名单 / 反垃圾 / DNS 信誉检查</p>
</div>
{nav()}
<div class="wrap">
{body}
</div>
</body>
</html>
"""


@app.get("/", response_class=HTMLResponse)
def index(_: bool = Depends(auth)):
    logs_1 = docker_logs_since(1)
    logs_7 = docker_logs_since(7)
    logs_30 = docker_logs_since(30)

    s1 = parse_stats(logs_1)
    s7 = parse_stats(logs_7)
    s30 = parse_stats(logs_30)
    q_count, _, _ = queue_info()
    status = container_status()

    body = f"""
<div class="grid">
  {stat_card("今日成功", s1["sent"], "ok")}
  {stat_card("今日退信", s1["bounced"], "bad")}
  {stat_card("今日延迟", s1["deferred"], "warn")}
  {stat_card("今日拒收", s1["reject"], "bad")}
  {stat_card("当前队列", q_count, "warn")}
  {stat_card("30 日成功", s30["sent"], "ok")}
</div>

<div class="grid">
  {stat_card("7 日成功", s7["sent"], "ok")}
  {stat_card("7 日退信", s7["bounced"], "bad")}
  {stat_card("7 日延迟", s7["deferred"], "warn")}
  {stat_card("30 日退信", s30["bounced"], "bad")}
  {stat_card("30 日延迟", s30["deferred"], "warn")}
  {stat_card("30 日拒收", s30["reject"], "bad")}
</div>

<div class="card">
  <h2>容器状态</h2>
  <pre>{escape(status)}</pre>
  <p class="muted">统计基于 docker-mailserver 日志解析。已经投递成功的邮件无法撤回；只有还在 Postfix 队列里的邮件可以取消。</p>
</div>
"""
    return layout("仪表盘", body)


@app.get("/stats", response_class=HTMLResponse)
def stats(_: bool = Depends(auth)):
    logs = docker_logs_since(30, 50000)
    daily = group_daily(logs, 30)
    top = extract_top(logs)

    max_sent = max([v["sent"] for v in daily.values()] + [1])
    rows = ""
    for day, s in daily.items():
        pct = int((s["sent"] / max_sent) * 100) if max_sent else 0
        rows += f"""
<tr>
<td>{day}</td>
<td><b class="ok">{s['sent']}</b></td>
<td><b class="bad">{s['bounced']}</b></td>
<td><b class="warn">{s['deferred']}</b></td>
<td><b class="bad">{s['reject']}</b></td>
<td><div class="bar"><span style="width:{pct}%"></span></div></td>
</tr>
"""

    def top_table(items, name):
        if not items:
            return f"<p class='muted'>{name} 暂无数据</p>"
        h = f"<h3>{name}</h3><table><tr><th>对象</th><th>次数</th></tr>"
        for k, v in items:
            h += f"<tr><td><code>{escape(k)}</code></td><td>{v}</td></tr>"
        h += "</table>"
        return h

    body = f"""
<div class="card">
  <h2>30 日发件趋势</h2>
  <table>
    <tr><th>日期</th><th>成功</th><th>退信</th><th>延迟</th><th>拒收</th><th>趋势</th></tr>
    {rows}
  </table>
</div>

<div class="grid2">
  <div class="card">{top_table(top["recipients"], "Top 收件人")}</div>
  <div class="card">{top_table(top["domains"], "Top 收件域名")}</div>
  <div class="card">{top_table(top["senders"], "Top 发件人")}</div>
  <div class="card">{top_table(top["ips"], "Top 连接 IP")}</div>
</div>
"""
    return layout("统计分析", body)


@app.get("/queue", response_class=HTMLResponse)
def queue(_: bool = Depends(auth)):
    count, q, ids = queue_info()

    id_options = ""
    for mid in ids[:200]:
        id_options += f"<option value='{escape(mid)}'>{escape(mid)}</option>"

    body = f"""
<div class="notice">
  <b>取消发送说明：</b>只有还在 Postfix 队列中的邮件可以取消。已经 status=sent 的邮件已经交给对方服务器，无法撤回。
</div>

<div class="grid">
  {stat_card("当前队列数量", count, "warn")}
  {stat_card("可操作邮件 ID", len(ids), "warn")}
</div>

<div class="card">
  <h2>队列操作</h2>
  <form method="post" action="/queue/delete-one" style="display:inline-block;margin-right:10px">
    <select name="message_id">{id_options}</select>
    <button class="btn-danger" onclick="return confirm('确认删除这个队列邮件？')">删除选中邮件</button>
  </form>

  <form method="post" action="/queue/flush" style="display:inline-block;margin-right:10px">
    <button class="btn-warn">立即重试投递</button>
  </form>

  <form method="post" action="/queue/delete-all" style="display:inline-block">
    <button class="btn-danger" onclick="return confirm('危险操作：确认清空全部邮件队列？')">清空全部队列</button>
  </form>
</div>

<div class="card">
  <h2>Postfix 队列详情</h2>
  <pre>{escape(q)}</pre>
</div>
"""
    return layout("队列/取消", body)


@app.post("/queue/delete-one")
def queue_delete_one(_: bool = Depends(auth), message_id: str = Form(...)):
    mid = re.sub(r"[^A-Fa-f0-9]", "", message_id)
    if mid:
        out = run(f"docker exec {MAIL_CONTAINER} postsuper -d {mid} 2>&1", timeout=20)
        audit("queue_delete_one", f"{mid}: {out}")
    return RedirectResponse("/queue", status_code=303)


@app.post("/queue/delete-all")
def queue_delete_all(_: bool = Depends(auth)):
    out = run(f"docker exec {MAIL_CONTAINER} postsuper -d ALL 2>&1", timeout=30)
    audit("queue_delete_all", out)
    return RedirectResponse("/queue", status_code=303)


@app.post("/queue/flush")
def queue_flush(_: bool = Depends(auth)):
    out = run(f"docker exec {MAIL_CONTAINER} postqueue -f 2>&1", timeout=30)
    audit("queue_flush", out)
    return RedirectResponse("/queue", status_code=303)


@app.get("/blacklist", response_class=HTMLResponse)
def blacklist(_: bool = Depends(auth)):
    bl = list_table("blocklist")
    sp = list_table("suppressions")

    body = f"""
<div class="notice">
  <b>说明：</b>退订名单用于以后你的群发/通知系统过滤收件人；黑名单用于记录和生成拦截策略。直接拦截 SMTP 需要额外写入 Postfix 规则，建议先记录，再确认规则后启用，避免误伤正常客户。
</div>

<div class="grid2">
  <div class="card">
    <h2>添加黑名单</h2>
    <form method="post" action="/blacklist/add">
      <p>
        <select name="type">
          <option value="email">邮箱</option>
          <option value="domain">域名</option>
          <option value="ip">IP</option>
        </select>
      </p>
      <p><input name="value" placeholder="bad@example.com / example.com / 1.2.3.4"></p>
      <p><input name="reason" placeholder="原因，可选"></p>
      <p>
        <select name="action">
          <option value="REJECT">REJECT 拒收</option>
          <option value="DISCARD">DISCARD 静默丢弃</option>
          <option value="HOLD">HOLD 暂存</option>
        </select>
      </p>
      <button>添加黑名单</button>
    </form>
  </div>

  <div class="card">
    <h2>添加退订 / 取消接收</h2>
    <form method="post" action="/suppressions/add">
      <p>
        <select name="type">
          <option value="email">邮箱</option>
          <option value="domain">域名</option>
        </select>
      </p>
      <p><input name="value" placeholder="user@example.com / example.com"></p>
      <p><input name="reason" placeholder="用户退订 / 投诉 / 退信"></p>
      <button>加入退订名单</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>黑名单</h2>
  {render_rows(bl)}
</div>

<div class="card">
  <h2>退订 / 取消接收名单</h2>
  {render_rows(sp)}
</div>

<div class="card">
  <h2>导出名单</h2>
  <p><a class="btn" href="/export/suppressions">导出退订名单</a> <a class="btn" href="/export/blocklist">导出黑名单</a></p>
</div>
"""
    return layout("黑名单/退订", body)


@app.post("/blacklist/add")
def blacklist_add(_: bool = Depends(auth), type: str = Form(...), value: str = Form(...), action: str = Form("REJECT"), reason: str = Form("")):
    value = value.strip().lower()
    type = type.strip().lower()
    action = action.strip().upper()
    if type not in ["email", "domain", "ip"]:
        return RedirectResponse("/blacklist", status_code=303)
    if action not in ["REJECT", "DISCARD", "HOLD"]:
        action = "REJECT"
    if value:
        conn = db()
        conn.execute(
            "INSERT OR IGNORE INTO blocklist(type, value, action, reason, created_at) VALUES (?, ?, ?, ?, ?)",
            (type, value, action, reason, datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")),
        )
        conn.commit()
        conn.close()
        audit("blacklist_add", f"{type} {value} {action} {reason}")
    return RedirectResponse("/blacklist", status_code=303)


@app.post("/suppressions/add")
def suppressions_add(_: bool = Depends(auth), type: str = Form(...), value: str = Form(...), reason: str = Form("")):
    value = value.strip().lower()
    type = type.strip().lower()
    if type not in ["email", "domain"]:
        return RedirectResponse("/blacklist", status_code=303)
    if value:
        conn = db()
        conn.execute(
            "INSERT OR IGNORE INTO suppressions(type, value, reason, created_at) VALUES (?, ?, ?, ?)",
            (type, value, reason, datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S UTC")),
        )
        conn.commit()
        conn.close()
        audit("suppression_add", f"{type} {value} {reason}")
    return RedirectResponse("/blacklist", status_code=303)


@app.post("/delete-record")
def delete_record(_: bool = Depends(auth), table: str = Form(...), id: int = Form(...)):
    if table not in ["blocklist", "suppressions"]:
        return RedirectResponse("/blacklist", status_code=303)
    conn = db()
    conn.execute(f"DELETE FROM {table} WHERE id=?", (id,))
    conn.commit()
    conn.close()
    audit("delete_record", f"{table} id={id}")
    return RedirectResponse("/blacklist", status_code=303)


@app.get("/export/suppressions", response_class=PlainTextResponse)
def export_suppressions(_: bool = Depends(auth)):
    rows = list_table("suppressions")
    return "\n".join([r["value"] for r in rows])


@app.get("/export/blocklist", response_class=PlainTextResponse)
def export_blocklist(_: bool = Depends(auth)):
    rows = list_table("blocklist")
    return "\n".join([f"{r['type']},{r['value']},{r['action']},{r['reason'] or ''}" for r in rows])


@app.get("/antispam", response_class=HTMLResponse)
def antispam(_: bool = Depends(auth), domain: str = "shy521.com"):
    logs = docker_logs_since(7, 30000)
    s = parse_stats(logs)
    dns = dns_check(domain)
    checks = postfix_security_status()

    risk = []
    if s["deferred"] > s["sent"] * 2 and s["deferred"] > 20:
        risk.append("延迟队列偏高，可能存在对方限速、IP 信誉不足或 DNS 配置问题。")
    if s["bounced"] > max(10, s["sent"] * 0.2):
        risk.append("退信比例偏高，建议清理无效收件人并启用退订过滤。")
    if dns["score"] < 80:
        risk.append("DNS 信誉配置不完整，建议检查 SPF / DKIM / DMARC / PTR。")
    if not risk:
        risk.append("当前没有发现明显高风险项。")

    risk_html = "".join([f"<li>{escape(x)}</li>" for x in risk])

    body = f"""
<div class="grid">
  {stat_card("7 日成功", s["sent"], "ok")}
  {stat_card("7 日退信", s["bounced"], "bad")}
  {stat_card("7 日延迟", s["deferred"], "warn")}
  {stat_card("DNS 评分", str(dns["score"]) + "/100", "ok" if dns["score"] >= 80 else "warn")}
</div>

<div class="card">
  <h2>风险建议</h2>
  <ul>{risk_html}</ul>
</div>

<div class="card">
  <h2>Rspamd / 反垃圾状态</h2>
  <pre>{escape(checks["rspamd"])}</pre>
</div>

<div class="card">
  <h2>Postfix 安全相关配置</h2>
  <pre>{escape(checks["postconf"])}</pre>
</div>

<div class="card">
  <h2>当前队列</h2>
  <pre>{escape(checks["mailq"])}</pre>
</div>
"""
    return layout("反垃圾", body)


@app.get("/dns", response_class=HTMLResponse)
def dns(_: bool = Depends(auth), domain: str = "shy521.com"):
    d = dns_check(domain)
    body = f"""
<div class="card">
  <h2>DNS 信誉检查</h2>
  <form method="get" action="/dns">
    <input name="domain" value="{escape(d['domain'])}" placeholder="example.com">
    <button>检查</button>
  </form>
</div>

<div class="grid">
  {stat_card("DNS 评分", str(d["score"]) + "/100", "ok" if d["score"] >= 80 else "warn")}
</div>

<div class="grid2">
  <div class="card"><h3>MX</h3><pre>{escape(d["mx"])}</pre></div>
  <div class="card"><h3>SPF</h3><pre>{escape(d["spf"])}</pre></div>
  <div class="card"><h3>DMARC</h3><pre>{escape(d["dmarc"])}</pre></div>
  <div class="card"><h3>DKIM mail._domainkey</h3><pre>{escape(d["dkim_mail"])}</pre></div>
  <div class="card"><h3>DKIM default._domainkey</h3><pre>{escape(d["dkim_default"])}</pre></div>
  <div class="card"><h3>mail A / PTR</h3><pre>A:\\n{escape(d["a"])}\\n\\nPTR:\\n{escape(d["ptr"])}</pre></div>
</div>
"""
    return layout("DNS 检查", body)


@app.get("/logs", response_class=HTMLResponse)
def logs(_: bool = Depends(auth), lines: int = 1000, keyword: str = ""):
    lines = max(100, min(lines, 10000))
    data = docker_logs_tail(lines)
    if keyword.strip():
        k = keyword.strip()
        data = "\n".join([x for x in data.splitlines() if k.lower() in x.lower()])

    body = f"""
<div class="card">
  <h2>最近日志</h2>
  <form method="get" action="/logs">
    <input name="keyword" value="{escape(keyword)}" placeholder="关键词：sent / bounced / gmail / reject">
    <input name="lines" value="{lines}" placeholder="行数">
    <button>筛选</button>
  </form>
  <pre>{escape(data)}</pre>
</div>
"""
    return layout("日志", body)


@app.get("/system", response_class=HTMLResponse)
def system(_: bool = Depends(auth)):
    st = system_status()
    audit_rows = list_table("audit_log")[:50]

    audit_html = "<table><tr><th>ID</th><th>动作</th><th>详情</th><th>时间</th></tr>"
    for r in audit_rows:
        audit_html += f"<tr><td>{r['id']}</td><td>{escape(r['action'])}</td><td>{escape(r['detail'])}</td><td>{escape(r['created_at'])}</td></tr>"
    audit_html += "</table>"

    body = f"""
<div class="grid2">
  <div class="card"><h2>负载</h2><pre>{escape(st["load"])}</pre></div>
  <div class="card"><h2>内存</h2><pre>{escape(st["mem"])}</pre></div>
  <div class="card"><h2>磁盘</h2><pre>{escape(st["disk"])}</pre></div>
  <div class="card"><h2>Docker Stats</h2><pre>{escape(st["docker"])}</pre></div>
</div>

<div class="card">
  <h2>操作审计</h2>
  {audit_html}
</div>
"""
    return layout("系统", body)


@app.get("/api/stats", response_class=PlainTextResponse)
def api_stats(_: bool = Depends(auth)):
    logs_1 = docker_logs_since(1)
    logs_7 = docker_logs_since(7)
    logs_30 = docker_logs_since(30)
    q_count, _, _ = queue_info()
    return json.dumps(
        {
            "today": parse_stats(logs_1),
            "seven_days": parse_stats(logs_7),
            "thirty_days": parse_stats(logs_30),
            "queue": q_count,
        },
        ensure_ascii=False,
        indent=2,
    )

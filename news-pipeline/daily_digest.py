#!/usr/bin/env python3
"""
iTIN Pakistan — Daily Digest Email
Sends a summary of all auto-published articles in the last 24 hours.
Runs daily at 9 AM UTC via cron.
"""

import json
import logging
import smtplib
import sqlite3
import sys
from datetime import datetime, timezone, timedelta
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from pathlib import Path

BASE_DIR = Path("/opt/itinpk-news")
CONFIG_PATH = BASE_DIR / "config.json"
DB_PATH = BASE_DIR / "processed_urls.db"
LOG_DIR = BASE_DIR / "logs"

LOG_DIR.mkdir(exist_ok=True)

def load_config():
    with open(CONFIG_PATH) as f:
        return json.load(f)

CFG = load_config()

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
log_file = LOG_DIR / f"digest_{datetime.now(timezone.utc).strftime('%Y-%m-%d')}.log"
logger = logging.getLogger("daily_digest")
logger.setLevel(logging.DEBUG)

fh = logging.FileHandler(log_file, encoding="utf-8")
fh.setLevel(logging.DEBUG)
fh.setFormatter(logging.Formatter("%(asctime)s [%(levelname)s] %(message)s"))
logger.addHandler(fh)

ch = logging.StreamHandler()
ch.setLevel(logging.INFO)
ch.setFormatter(logging.Formatter("[%(levelname)s] %(message)s"))
logger.addHandler(ch)

# ---------------------------------------------------------------------------
# Fetch articles from last 24 hours
# ---------------------------------------------------------------------------
def get_recent_articles():
    if not DB_PATH.exists():
        logger.warning("Database not found — no articles to report")
        return []

    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row

    cutoff = (datetime.now(timezone.utc) - timedelta(hours=24)).isoformat()
    rows = conn.execute(
        "SELECT url, category, status, title, created_at FROM processed_urls WHERE created_at >= ? ORDER BY created_at DESC",
        (cutoff,),
    ).fetchall()
    conn.close()
    return rows

# ---------------------------------------------------------------------------
# Build email HTML
# ---------------------------------------------------------------------------
def build_email_html(articles):
    published = [a for a in articles if a["status"] == "published"]
    drafted = [a for a in articles if a["status"] == "drafted"]

    site_url = CFG["wordpress"]["site_url"]
    now_str = datetime.now(timezone.utc).strftime("%B %d, %Y at %H:%M UTC")

    html = f"""<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;max-width:650px;margin:0 auto;padding:20px;color:#333;">
<div style="background:#37215F;color:white;padding:20px 24px;border-radius:8px 8px 0 0;">
<h1 style="margin:0;font-size:20px;">iTIN Pakistan — Daily News Digest</h1>
<p style="margin:5px 0 0;font-size:13px;opacity:0.8;">{now_str}</p>
</div>

<div style="background:#f8f9fa;padding:20px 24px;border:1px solid #e9ecef;">
<div style="display:flex;gap:20px;margin-bottom:20px;">
<div style="flex:1;background:#d4edda;padding:12px;border-radius:6px;text-align:center;">
<div style="font-size:28px;font-weight:bold;color:#155724;">{len(published)}</div>
<div style="font-size:12px;color:#155724;">Published</div>
</div>
<div style="flex:1;background:#fff3cd;padding:12px;border-radius:6px;text-align:center;">
<div style="font-size:28px;font-weight:bold;color:#856404;">{len(drafted)}</div>
<div style="font-size:12px;color:#856404;">Drafts (Review Needed)</div>
</div>
<div style="flex:1;background:#f8f9fa;padding:12px;border-radius:6px;text-align:center;">
<div style="font-size:28px;font-weight:bold;color:#333;">{len(articles)}</div>
<div style="font-size:12px;color:#555;">Total Processed</div>
</div>
</div>
"""

    if published:
        html += '<h2 style="color:#155724;font-size:16px;border-bottom:2px solid #28a745;padding-bottom:8px;">Auto-Published Articles</h2>'
        for a in published:
            html += f"""
<div style="background:white;padding:12px 16px;margin:8px 0;border-radius:6px;border-left:4px solid #28a745;">
<div style="font-size:11px;color:#666;text-transform:uppercase;">{a['category']}</div>
<div style="font-weight:600;margin:4px 0;"><a href="{site_url}/?p=" style="color:#37215F;text-decoration:none;">{a['title']}</a></div>
<div style="font-size:12px;color:#999;">{a['created_at'][:16]} UTC</div>
<div style="font-size:11px;margin-top:4px;"><a href="{a['url']}" style="color:#0881BE;">Source article &rarr;</a></div>
</div>"""

    if drafted:
        html += '<h2 style="color:#856404;font-size:16px;border-bottom:2px solid #ffc107;padding-bottom:8px;margin-top:24px;">Drafts — Manual Review Needed</h2>'
        html += f'<p style="font-size:13px;color:#666;">Log in to <a href="{site_url}/wp-admin/edit.php?post_status=draft">WordPress Admin</a> to review and publish.</p>'
        for a in drafted:
            html += f"""
<div style="background:white;padding:12px 16px;margin:8px 0;border-radius:6px;border-left:4px solid #ffc107;">
<div style="font-size:11px;color:#666;text-transform:uppercase;">{a['category']}</div>
<div style="font-weight:600;margin:4px 0;">{a['title']}</div>
<div style="font-size:12px;color:#999;">{a['created_at'][:16]} UTC</div>
<div style="font-size:11px;margin-top:4px;"><a href="{a['url']}" style="color:#0881BE;">Source article &rarr;</a></div>
</div>"""

    if not articles:
        html += '<p style="text-align:center;color:#666;padding:30px 0;">No new articles were processed in the last 24 hours.</p>'

    html += f"""
</div>

<div style="text-align:center;padding:16px;font-size:11px;color:#999;">
<p>Sent by iTIN Pakistan News Pipeline | <a href="{site_url}" style="color:#0881BE;">Visit Site</a> | <a href="{site_url}/wp-admin/" style="color:#0881BE;">Admin Panel</a></p>
</div>
</body>
</html>"""

    return html

# ---------------------------------------------------------------------------
# Send email via Brevo SMTP
# ---------------------------------------------------------------------------
def send_email(html_content, article_count):
    smtp_cfg = CFG["brevo_smtp"]
    admin_email = smtp_cfg["admin_email"]

    msg = MIMEMultipart("alternative")
    msg["Subject"] = f"iTIN Pakistan Daily Digest — {article_count} articles ({datetime.now(timezone.utc).strftime('%b %d')})"
    msg["From"] = f"{smtp_cfg['from_name']} <{smtp_cfg['from_email']}>"
    msg["To"] = admin_email
    msg["Reply-To"] = smtp_cfg["from_email"]

    msg.attach(MIMEText(html_content, "html"))

    try:
        with smtplib.SMTP(smtp_cfg["host"], smtp_cfg["port"]) as server:
            server.starttls()
            server.login(smtp_cfg["username"], smtp_cfg["password"])
            server.sendmail(smtp_cfg["from_email"], [admin_email], msg.as_string())
        logger.info(f"Digest email sent to {admin_email} ({article_count} articles)")
        return True
    except Exception as e:
        logger.error(f"Failed to send digest email: {e}")
        return False

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def run_digest():
    logger.info("=" * 50)
    logger.info("Daily Digest — starting")
    articles = get_recent_articles()
    logger.info(f"Found {len(articles)} articles from last 24 hours")

    html = build_email_html(articles)
    sent = send_email(html, len(articles))

    logger.info(f"Digest complete. Sent: {sent}")
    return sent

if __name__ == "__main__":
    try:
        run_digest()
    except Exception as e:
        logger.exception(f"Digest crashed: {e}")
        sys.exit(1)

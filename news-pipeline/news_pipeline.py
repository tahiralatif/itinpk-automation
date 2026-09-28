#!/usr/bin/env python3
"""
iTIN Pakistan Automated News Pipeline
Fetches news from Google News RSS or NewsAPI.org, rewrites via Groq AI, publishes to WordPress.
Runs via cron: every 30 min (Google News) or every 1 hour (NewsAPI).
Usage: python3 news_pipeline.py --source google|newsapi
"""

import json
import logging
import os
import re
import sqlite3
import subprocess
import sys
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone, timedelta
from difflib import SequenceMatcher
from html import unescape
from pathlib import Path
from urllib.parse import urlparse

import feedparser

# ---------------------------------------------------------------------------
# Image Extraction
# ---------------------------------------------------------------------------
def fetch_og_image(url, timeout=10):
    """Fetch the Open Graph image URL from a page's meta tags. Follows redirects."""
    try:
        req = urllib.request.Request(url, headers={
            "User-Agent": "Mozilla/5.0 (compatible; iTIN-Pakistan-NewsBot/1.0)",
        })
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            final_url = resp.geturl()
            html = resp.read(100000).decode("utf-8", errors="ignore")

        og_match = re.search(r'<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']', html, re.I)
        if og_match:
            img = og_match.group(1)
            if img.startswith("http"):
                return img

        tw_match = re.search(r'<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']', html, re.I)
        if tw_match:
            img = tw_match.group(1)
            if img.startswith("http"):
                return img

        img_match = re.search(r'<meta[^>]+content=["\']([^"\']+\.(jpg|jpeg|png|webp))["\'][^>]+property=["\']og:image["\']', html, re.I)
        if img_match:
            return img_match.group(1)
    except Exception:
        pass
    return None

def fetch_article_content(url, timeout=10):
    """Fetch article text content from a URL. Follows redirects (Google News).
    Returns (content, final_url) tuple."""
    try:
        req = urllib.request.Request(url, headers={
            "User-Agent": "Mozilla/5.0 (compatible; iTIN-Pakistan-NewsBot/1.0)",
        })
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            final_url = resp.geturl()
            html = resp.read(150000).decode("utf-8", errors="ignore")

        # Extract text from <p> tags
        paragraphs = re.findall(r'<p[^>]*>(.*?)</p>', html, re.DOTALL | re.I)
        text_parts = []
        for p in paragraphs:
            clean = re.sub(r'<[^>]+>', '', p).strip()
            if len(clean) > 30:
                text_parts.append(clean)
        content = ' '.join(text_parts)

        # If no <p> tags, try extracting from article body tags
        if len(content) < 200:
            body_match = re.search(r'<article[^>]*>(.*?)</article>', html, re.DOTALL | re.I)
            if body_match:
                content = re.sub(r'<[^>]+>', ' ', body_match.group(1)).strip()
                content = re.sub(r'\s+', ' ', content)

        return (content[:2000] if content else "", final_url)
    except Exception as e:
        logger.debug(f"  Content fetch failed for {url[:60]}: {e}")
        return ("", url)

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
BASE_DIR = Path("/opt/itinpk-news")
CONFIG_PATH = BASE_DIR / "config.json"
DB_PATH = BASE_DIR / "processed_urls.db"
LOG_DIR = BASE_DIR / "logs"

LOG_DIR.mkdir(exist_ok=True)

# Topic guardrails — strict content filtering
sys.path.insert(0, str(BASE_DIR))
from topic_guardrails import filter_article, is_video_topic_allowed

def load_config():
    with open(CONFIG_PATH) as f:
        cfg = json.load(f)
    
    # Override with .env values if available (more secure)
    try:
        sys.path.insert(0, str(BASE_DIR))
        from config_loader import get_env
        if get_env("GROQ_API_KEY"):
            cfg["groq_api_key"] = get_env("GROQ_API_KEY")
        if get_env("NEWSAPI_KEY"):
            cfg["newsapi"]["api_key"] = get_env("NEWSAPI_KEY")
        if get_env("YOUTUBE_API_KEY"):
            cfg["youtube"]["api_key"] = get_env("YOUTUBE_API_KEY")
        if get_env("OPENROUTER_API_KEY"):
            cfg["openrouter"]["api_key"] = get_env("OPENROUTER_API_KEY")
        if get_env("OPENROUTER_MODEL"):
            cfg["openrouter"]["model"] = get_env("OPENROUTER_MODEL")
    except ImportError:
        pass  # Fall back to config.json values
    
    return cfg

CFG = load_config()

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
run_ts = datetime.now(timezone.utc).strftime("%Y-%m-%d_%H%M")
log_file = LOG_DIR / f"run_{run_ts}.log"

logger = logging.getLogger("news_pipeline")
logger.setLevel(logging.DEBUG)

fh = logging.FileHandler(log_file, encoding="utf-8")
fh.setLevel(logging.DEBUG)
fh.setFormatter(logging.Formatter("%(asctime)s [%(levelname)s] %(message)s"))

ch = logging.StreamHandler()
ch.setLevel(logging.INFO)
ch.setFormatter(logging.Formatter("[%(levelname)s] %(message)s"))

logger.addHandler(fh)
logger.addHandler(ch)

# ---------------------------------------------------------------------------
# SQLite — dedup database
# ---------------------------------------------------------------------------
def init_db():
    conn = sqlite3.connect(DB_PATH)
    conn.execute("""
        CREATE TABLE IF NOT EXISTS processed_urls (
            url TEXT PRIMARY KEY,
            category TEXT,
            status TEXT,
            title TEXT,
            source TEXT DEFAULT 'google',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    try:
        conn.execute("ALTER TABLE processed_urls ADD COLUMN source TEXT DEFAULT 'google'")
    except sqlite3.OperationalError:
        pass
    # Used image tracking table — prevents reuse of the same stock photo
    conn.execute("""
        CREATE TABLE IF NOT EXISTS used_images (
            image_url TEXT PRIMARY KEY,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    conn.commit()
    return conn

def is_url_processed(conn, url):
    row = conn.execute("SELECT 1 FROM processed_urls WHERE url = ?", (url,)).fetchone()
    return row is not None

def normalize_url(url):
    """Normalize URL for dedup — extract actual URL from Google News redirect if possible."""
    if "news.google.com" in url:
        match = re.search(r'url=([^&]+)', url)
        if match:
            return urllib.parse.unquote(match.group(1))
    return url

def _normalize_title(title):
    """Normalize title for comparison: lowercase, remove stop words, collapse whitespace."""
    t = title.lower().strip()
    # Remove punctuation but keep decimal numbers
    t = re.sub(r"[^\w\s.]", "", t)
    # Remove stop words
    stop_words = {'the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'has', 'have', 'had', 'been', 'will', 'would', 'could', 'should', 'may', 'might', 'its', 'it', 'that', 'this', 'these', 'those', 'and', 'or', 'but', 'not', 'no', 'nor'}
    words = [w for w in t.split() if w not in stop_words]
    t = ' '.join(words)
    t = re.sub(r"\s+", " ", t).strip()
    return t

def is_title_similar(conn, title, threshold=0.75):
    """Check if a similar title already exists in the database (cross-source dedup).
    
    Uses a higher threshold (0.75) to avoid false positives on different articles
    that happen to share common words (e.g. 'Boost' vs 'Bolster' variants).
    Also checks against WordPress post titles directly for cross-source dedup.
    """
    clean = _normalize_title(title)
    
    # 1) Check against SQLite dedup DB
    rows = conn.execute("SELECT title FROM processed_urls WHERE title IS NOT NULL AND title != ''").fetchall()
    for (existing,) in rows:
        existing_clean = _normalize_title(existing)
        ratio = SequenceMatcher(None, clean, existing_clean).ratio()
        if ratio >= threshold:
            return True, existing, ratio
    
    # 2) Check against WordPress post titles (cross-source dedup)
    wp_titles = _get_wp_titles()
    for wp_title in wp_titles:
        wp_clean = _normalize_title(wp_title)
        ratio = SequenceMatcher(None, clean, wp_clean).ratio()
        if ratio >= threshold:
            return True, wp_title, ratio
    
    return False, None, 0

def _get_wp_titles():
    """Fetch published + draft post titles from WordPress database for dedup."""
    try:
        wp = CFG["wordpress"]["wp_path"]
        site_root = CFG["wordpress"]["site_root"]
        cmd = [
            wp, "post", "list",
            "--post_type=post",
            "--post_status=publish,draft",
            "--fields=post_title",
            "--format=csv",
            "--allow-root",
            f"--path={site_root}",
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
        if result.returncode == 0:
            titles = [line.strip() for line in result.stdout.strip().split('\n') if line.strip() and line.strip() != 'post_title']
            return titles
    except Exception as e:
        logger.debug(f"  WP title fetch failed: {e}")
    return []

# Topic relevance — only IT/tech/startup/cybersecurity/AI/review content
TECH_TOPIC_KEYWORDS = [
    "tech", "it ", "cyber", "ai ", "artificial intelligence", "cloud", "startup",
    "digital", "software", "hardware", "app", "data", "internet", "5g", "4g",
    "smartphone", "gadget", "review", "apple", "google", "microsoft", "amazon",
    "meta", "openai", "chatgpt", "gpu", "cpu", "chip", "semiconductor",
    "fintech", "edtech", "saas", "blockchain", "crypto", "bitcoin", "pkr",
    "pakistan tech", "it export", "it minister", "moitt", "ptcl", "jazz",
    "telenor", "zong", "ufone", "nayatel", "akeelabs", "akea", "Systems Limited",
    "fbr", "fbr tax", "pseb", "HITEC", "nust", "lums", "fast nuces",
    "startup funding",
    "venture capital", "seed round", "series a", "series b",
    "database", "server", "network", "security", "encryption", "malware",
    "ransomware", "phishing", "firewall", "vpn", "iot", "machine learning",
    "deep learning", "neural", "automation", "robot", "drone", "3d printing",
    "ecommerce", "e-commerce", "fintech", "digital payment", "mobile wallet",
    "easypaisa", "jazzcash", "sadapay", "nayapay", "bank alflah",
    "nvidia", "intel", "amd", "qualcomm", "samsung", "huawei", "xiaomi",
    "oneplus", "realme", "oppo", "vivo", "tesla", "spacex", "blue origin",
    "space tech", "edtech", "healthtech", "agritech", "legaltech",
    "cloud computing", "aws", "azure", "google cloud", "devops", "kubernetes",
    "docker", "linux", "windows", "macos", "ios", "android",
]

def is_topic_relevant(title, summary=""):
    """Check if article is relevant to IT/tech/startup/cybersecurity/AI/review topics."""
    text = (title + " " + summary).lower()
    for kw in TECH_TOPIC_KEYWORDS:
        if kw in text:
            return True
    return False

def mark_url(conn, url, category, status, title="", source="google"):
    """Mark a URL as processed. All statuses are recorded to prevent reprocessing."""
    conn.execute(
        "INSERT OR REPLACE INTO processed_urls (url, category, status, title, source) VALUES (?, ?, ?, ?, ?)",
        (url, category, status, title, source),
    )
    conn.commit()

def mark_image_used(conn, image_url):
    """Record that an image URL has been used to prevent reuse."""
    conn.execute(
        "INSERT OR IGNORE INTO used_images (image_url) VALUES (?)",
        (image_url,),
    )
    conn.commit()

def is_image_used(conn, image_url):
    """Check if an image URL has already been used."""
    row = conn.execute("SELECT 1 FROM used_images WHERE image_url = ?", (image_url,)).fetchone()
    return row is not None

# ---------------------------------------------------------------------------
# RSS Fetcher — Google News RSS
# ---------------------------------------------------------------------------
def build_rss_url(query):
    encoded = urllib.parse.quote_plus(query)
    return f"https://news.google.com/rss/search?q={encoded}&hl=en-PK&gl=PK&ceid=PK:en"

def score_source(link, title, trusted):
    """Score an article — higher = better. Trusted sources get a big boost."""
    score = 0
    link_lower = link.lower()
    title_lower = title.lower()
    for src in trusted:
        if src in link_lower:
            score += 100
            break
    if any(w in title_lower for w in ["pakistan", "karachi", "lahore", "islamabad", "pkr", "rs "]):
        score += 10
    return score

# ---------------------------------------------------------------------------
# RSS Fetcher — Direct RSS Feeds (TechJuice, ProPakistani, etc.)
# ---------------------------------------------------------------------------
ARY_TECH_KEYWORDS = [
    "it", "software", "hardware", "tech", "technology", "digital", "startup",
    "entrepreneur", "funding", "venture", "investment", "cloud", "ai",
    "artificial intelligence", "machine learning", "cybersecurity", "security",
    "data", "5g", "4g", "telecom", "broadband", "internet", "app",
    "smartphone", "gadget", "review", "fintech", "edtech", "saas",
    "ecommerce", "e-commerce", "blockchain", "crypto", "bitcoin",
    "database", "server", "network", "encryption", "malware", "ransomware",
    "innovation", "startup fund", "venture fund", "tech park", "it export",
    " coding", "programming", "developer", "devops", "kubernetes", "docker",
    "aws", "azure", "google cloud", "nvidia", "intel", "amd", "chip",
    "semiconductor", "robot", "drone", "iot", "automation", "digital payment",
    "mobile wallet", "easypaisa", "jazzcash", "ptcl", "jazz", "telenor",
    "nayatel", "systems limited", "netsol", "trg", "mufti", "sadapay", "nayapay",
]

def is_ary_tech_article(title, summary=""):
    """Check if an ARY News article is actually tech-related."""
    text = (title + " " + summary).lower()
    matches = sum(1 for kw in ARY_TECH_KEYWORDS if kw in text)
    return matches >= 2  # Require at least 2 tech keywords

def fetch_direct_rss_articles(rss_url, category_slug, category_name, max_articles, trusted, source_key=""):
    """Fetch articles from a direct RSS feed URL (not Google News)."""
    logger.info(f"Fetching direct RSS: {rss_url}")
    
    try:
        feed = feedparser.parse(rss_url)
    except Exception as e:
        logger.error(f"RSS fetch failed for '{rss_url}': {e}")
        return []
    
    if feed.bozo and not feed.entries:
        logger.warning(f"RSS feed error for '{rss_url}': {feed.bozo_exception}")
        return []
    
    articles = []
    raw_entries = []
    for entry in feed.entries:
        link = entry.get("link", "")
        title = unescape(entry.get("title", ""))
        summary = unescape(entry.get("summary", ""))
        published = entry.get("published", "")
        
        if not link or not title:
            continue
        
        # Strict tech filtering for ARY News (general news outlet)
        if source_key == "arynews" and not is_ary_tech_article(title, summary):
            logger.info(f"  SKIP (non-tech ARY article): {title[:60]}")
            continue
        
        # Extract source name from URL
        parsed_link = urlparse(link)
        source_name = parsed_link.netloc.replace("www.", "").split(".")[0].capitalize()
        source_url = link
        
        # Get image
        image_url = None
        media = entry.get("media_content", [])
        if media and isinstance(media, list):
            for m in media:
                if m.get("medium") == "image" or "image" in m.get("type", ""):
                    image_url = m.get("url")
                    break
        
        score = score_source(link, title, trusted)
        raw_entries.append({
            "link": link, "title": title, "summary": summary,
            "published": published, "source_url": source_url,
            "source_name": source_name, "image_url": image_url, "score": score,
        })
    
    # Sort by score and fetch content for top entries
    raw_entries.sort(key=lambda a: a["score"], reverse=True)
    fetch_limit = min(10, len(raw_entries))
    logger.info(f"  Content fetch limit: {fetch_limit} of {len(raw_entries)} entries")
    
    for entry in raw_entries[:fetch_limit]:
        link = entry["link"]
        title = entry["title"]
        summary = entry["summary"]
        
        # Clean summary
        clean_summary = re.sub(r'<[^>]+>', '', summary).strip()
        min_chars = CFG["pipeline"].get("min_summary_length", 40)
        final_url = link
        
        # Fetch content if summary is too short
        if len(clean_summary) < min_chars or clean_summary.lower() == title.lower():
            logger.info(f"  Fetching full content for: {title[:50]}...")
            fetched, final_url = fetch_article_content(link, timeout=6)
            if fetched and len(fetched) > 100:
                summary = fetched[:2000]
                logger.info(f"  -> Got {len(fetched)} chars of content")
        
        # Get image if not already set
        if not image_url:
            image_url = fetch_og_image(final_url)
        if not image_url:
            image_url = fetch_og_image(link)
        
        articles.append({
            "url": link,
            "source_url": final_url if final_url != link else entry["source_url"],
            "source_name": entry["source_name"],
            "title": title,
            "summary": summary[:2000],
            "published": published,
            "category_slug": category_slug,
            "category_name": category_name,
            "image_url": image_url,
            "score": entry["score"],
        })
    
    articles.sort(key=lambda a: a["score"], reverse=True)
    selected = articles[:max_articles]
    logger.info(f"  -> {len(articles)} found, {len(selected)} selected from {rss_url}")
    return selected

def fetch_rss_articles(query, category_slug, category_name, max_articles, trusted):
    rss_url = build_rss_url(query)
    logger.info(f"Fetching RSS: {query} -> {rss_url}")

    try:
        feed = feedparser.parse(rss_url)
    except Exception as e:
        logger.error(f"RSS fetch failed for '{query}': {e}")
        return []

    if feed.bozo and not feed.entries:
        logger.warning(f"RSS feed error for '{query}': {feed.bozo_exception}")
        return []

    articles = []
    # First pass: collect all entries with basic info
    raw_entries = []
    for entry in feed.entries:
        link = entry.get("link", "")
        title = unescape(entry.get("title", ""))
        summary = unescape(entry.get("summary", ""))
        published = entry.get("published", "")

        if not link or not title:
            continue

        source_info = entry.get("source", {})
        source_url = source_info.get("href", "") if isinstance(source_info, dict) else ""
        source_name = source_info.get("title", "") if isinstance(source_info, dict) else ""
        if not source_url:
            source_url = link
        if not source_name:
            parsed_link = urlparse(link)
            source_name = parsed_link.netloc.replace("www.", "").split(".")[0].capitalize()
        source_name = re.sub(r'^\|?\s*', '', source_name).strip()
        if not source_name:
            source_name = "Unknown"

        image_url = None
        media = entry.get("media_content", [])
        if media and isinstance(media, list):
            for m in media:
                if m.get("medium") == "image" or "image" in m.get("type", ""):
                    image_url = m.get("url")
                    break

        score = score_source(link, title, trusted)
        raw_entries.append({
            "link": link, "title": title, "summary": summary,
            "published": published, "source_url": source_url,
            "source_name": source_name, "image_url": image_url, "score": score,
        })

    # Sort by score and only fetch full content for top 10
    raw_entries.sort(key=lambda a: a["score"], reverse=True)
    fetch_limit = min(10, len(raw_entries))
    logger.info(f"  Content fetch limit: {fetch_limit} of {len(raw_entries)} entries")

    for entry in raw_entries[:fetch_limit]:
        link = entry["link"]
        title = entry["title"]
        summary = entry["summary"]

        # Google News RSS summary is just the title in an anchor tag — fetch actual content
        clean_summary = re.sub(r'<[^>]+>', '', summary).strip()
        min_chars = CFG["pipeline"].get("min_summary_length", 40)
        final_url = link
        if len(clean_summary) < min_chars or clean_summary.lower() == title.lower():
            logger.info(f"  Fetching full content for: {title[:50]}...")
            fetched, final_url = fetch_article_content(link, timeout=6)
            if fetched and len(fetched) > 100:
                summary = fetched[:2000]
                logger.info(f"  -> Got {len(fetched)} chars of content")

        # Source info already extracted in first pass — use resolved URL if available
        source_url = final_url if final_url != link else entry["source_url"]
        source_name = entry["source_name"]
        image_url = entry["image_url"]
        published = entry["published"]

        if not image_url:
            image_url = fetch_og_image(final_url)
        if not image_url:
            image_url = fetch_og_image(link)
        if not image_url and source_url and "news.google.com" not in source_url:
            image_url = fetch_og_image(source_url)

        articles.append({
            "url": link,
            "source_url": source_url,
            "source_name": source_name,
            "title": title,
            "summary": summary[:2000],
            "published": published,
            "category_slug": category_slug,
            "category_name": category_name,
            "image_url": image_url,
            "score": entry["score"],
        })

    articles.sort(key=lambda a: a["score"], reverse=True)
    selected = articles[:max_articles]
    logger.info(f"  -> {len(articles)} found, {len(selected)} selected for '{query}'")
    return selected

# ---------------------------------------------------------------------------
# NewsAPI.org Fetcher
# ---------------------------------------------------------------------------
def fetch_newsapi_articles(query, category_slug, category_name, max_articles, trusted):
    """Fetch articles from NewsAPI.org."""
    api_cfg = CFG.get("newsapi", {})
    api_key = api_cfg.get("api_key", "")
    if not api_key:
        logger.warning("NewsAPI key not configured — skipping")
        return []

    base_url = api_cfg.get("base_url", "https://newsapi.org/v2")
    language = api_cfg.get("language", "en")
    sort_by = api_cfg.get("sort_by", "publishedAt")
    page_size = min(api_cfg.get("page_size", 10), max_articles * 2)

    params = urllib.parse.urlencode({
        "q": query,
        "language": language,
        "sortBy": sort_by,
        "pageSize": page_size,
        "apiKey": api_key,
    })
    url = f"{base_url}/everything?{params}"
    logger.info(f"Fetching NewsAPI: {query}")

    try:
        req = urllib.request.Request(url, headers={"User-Agent": "iTIN-Pakistan-NewsBot/1.0"})
        with urllib.request.urlopen(req, timeout=CFG["pipeline"]["rss_fetch_timeout"]) as resp:
            data = json.loads(resp.read().decode())
    except Exception as e:
        logger.error(f"NewsAPI fetch failed for '{query}': {e}")
        return []

    if data.get("status") != "ok":
        logger.warning(f"NewsAPI error: {data.get('message', 'unknown')}")
        return []

    articles = []
    for item in data.get("articles", []):
        link = item.get("url", "")
        title = item.get("title", "")
        description = item.get("description", "") or ""
        source_name = item.get("source", {}).get("name", "")
        published = item.get("publishedAt", "")

        if not link or not title or title == "[Removed]":
            continue

        # Pakistan relevance check — reject non-Pakistan articles
        combined_text = f"{title} {description}".lower()
        pakistan_keywords = [
            "pakistan", "pakistani", "karachi", "lahore", "islamabad", "pindi",
            "ptcl", "jazz", "telenor", "ufone", "zong", "nayatel", "systems limited",
            "ignite", "secp", "sbp", "pta", "moitt", "pseb", "digital pakistan",
            "crypto", "bitcoin", "ethereum", "fintech", "easypaisa", "jazzcash",
        ]
        has_pakistan = any(kw in combined_text for kw in pakistan_keywords)
        
        # Global tech companies — always allowed
        global_tech = ["apple", "google", "microsoft", "amazon", "meta", "openai", "nvidia"]
        is_global_tech = any(kw in combined_text for kw in global_tech)
        
        if not has_pakistan and not is_global_tech:
            logger.debug(f"      SKIP (not Pakistan-related): {title[:60]}")
            continue

        summary = description
        if not summary and item.get("content"):
            summary = item["content"]

        score = score_source(link, title, trusted)
        if source_name.lower() in [s.lower() for s in trusted]:
            score += 100

        articles.append({
            "url": link,
            "title": title,
            "summary": summary[:500],
            "published": published,
            "category_slug": category_slug,
            "category_name": category_name,
            "source_name": source_name,
            "image_url": item.get("urlToImage"),
            "score": score,
        })

    articles.sort(key=lambda a: a["score"], reverse=True)
    selected = articles[:max_articles]
    logger.info(f"  -> {len(articles)} found, {len(selected)} selected for '{query}' (NewsAPI)")
    return selected

# ---------------------------------------------------------------------------
# Groq AI — Rewrite
# ---------------------------------------------------------------------------
_ai_client_cache = {}

def _get_client(provider):
    """Get or create an OpenAI client for the given provider."""
    if provider not in _ai_client_cache:
        from openai import OpenAI
        if provider == "openrouter":
            cfg = CFG.get("openrouter", {})
            _ai_client_cache[provider] = OpenAI(
                api_key=cfg.get("api_key", ""),
                base_url=cfg.get("base_url", "https://openrouter.ai/api/v1"),
            )
        else:
            _ai_client_cache[provider] = OpenAI(
                api_key=CFG["groq_api_key"],
                base_url=CFG["groq_api_url"],
            )
    return _ai_client_cache[provider]

def ai_request(messages, temperature=0.7, max_tokens=1024):
    """Make an AI request. Tries Groq first, falls back to OpenRouter on rate limit."""
    providers = ["groq"]
    or_key = CFG.get("openrouter", {}).get("api_key", "")
    if or_key and or_key != "YOUR_OPENROUTER_API_KEY_HERE":
        providers.append("openrouter")

    last_error = None
    for provider in providers:
        client = _get_client(provider)
        model = CFG["groq_model"] if provider == "groq" else CFG["openrouter"]["model"]

        try:
            response = client.chat.completions.create(
                model=model,
                messages=messages,
                temperature=temperature,
                max_tokens=max_tokens,
            )
            content = response.choices[0].message.content
            if content:
                return content.strip(), provider
            last_error = f"Empty response from {provider}"
        except Exception as e:
            err_str = str(e).lower()
            is_rate_limit = any(kw in err_str for kw in [
                "rate_limit", "429", "tokens per", "tokens per day",
                "quota", "limit exceeded", "too many requests",
            ])
            is_insufficient_balance = any(kw in err_str for kw in [
                "insufficient balance", "402", "payment required",
            ])
            if is_insufficient_balance:
                logger.warning(f"  {provider} has insufficient balance, skipping...")
                last_error = f"{provider}: insufficient balance"
                continue
            elif is_rate_limit:
                logger.warning(f"  {provider} rate limited, trying fallback...")
                last_error = f"{provider}: {str(e)[:100]}"
                continue
            else:
                logger.error(f"  {provider} error: {e}")
                last_error = f"{provider}: {str(e)[:100]}"
                continue

    raise Exception(f"All providers failed. Last error: {last_error}")

def rewrite_article(article):
    """Use Groq AI to rewrite an article in original wording."""
    source_title = article["title"]
    source_summary = re.sub(r'<[^>]+>', '', article["summary"]).strip()
    category = article["category_name"]

    political_keywords = [
        "imran khan", "pti", "pmln", "ppp", "election", "ballot",
        "parliament", "senate", "na ", "assembly", "vote", "protest",
        "contempt", "jail", "prison", "corruption", "scandal",
        "khan", "sharif", "zardari", "maryam", "dar",
    ]
    title_lower = source_title.lower()
    if any(kw in title_lower for kw in political_keywords):
        logger.info(f"      Skipping political article: {source_title[:60]}")
        return {"headline": source_title, "body": source_summary, "tags": [], "_political": True}

    prompt = f"""You are a professional tech journalist writing for iTIN PAKISTAN (itinpk.14.jugaar.ai), a Pakistan-focused technology news portal.

CRITICAL RULE — FACTUAL ACCURACY:
You must preserve ALL factual claims from the source EXACTLY as stated. This means:
- Dates, years, and timeframes must match the source EXACTLY. Do NOT change "August 2026" to "July 2026". Do NOT invent specific dates.
- Organization names must match EXACTLY. Do NOT abbreviate, expand, or rename organizations.
- Numbers, percentages, and monetary amounts must match EXACTLY.
- Location names must match EXACTLY.
- People's names and titles must match EXACTLY.
- If the source says "Experts from Russia and Pakistan", do NOT change it to "Russian and Pakistani officials".
- If a fact is NOT in the source, do NOT invent it. Omit it rather than guess.
- If you are unsure whether a detail is preserved correctly, err on the side of omission.

{"IMPORTANT: The source text below is BRIEF (only a headline/short summary). You MUST write a detailed article (300-500 words) that sticks STRICTLY to the facts in the source. Expand on the implications, context, and significance of the news. Do NOT invent details, but DO provide analysis and context based on the facts provided. It is better to have a thorough accurate article than a short one." if len(source_summary) < 400 else ""}

Rewriting rules:
- Write {"300-500" if len(source_summary) < 400 else "400-600"} words, professional and factual
- Rewrite in your own original wording — do NOT copy sentences
- Write in a news article style with a clear headline
- Focus on the Pakistan angle where relevant
- Do NOT include placeholder text like [INSERT], TBD, or TODO
- Do NOT mention that this was rewritten or AI-generated
- Do NOT add specific dates, names, or details that are not in the source

Return your response in this exact JSON format:
{{"headline": "Your headline here", "body": "Your article body here", "tags": ["tag1", "tag2", "tag3"]}}

Original article:
Title: {source_title}
Summary: {source_summary}
Category: {category}"""

    messages = [
        {"role": "system", "content": "You are a tech news writer. Always respond with valid JSON only."},
        {"role": "user", "content": prompt},
    ]

    for attempt in range(3):
        try:
            result, provider = ai_request(messages, temperature=0.7, max_tokens=4096)
            result = result.strip()
            if not result:
                logger.warning(f"      Empty response from {provider} (attempt {attempt+1}), retrying...")
                time.sleep(2)
                continue
            if result.startswith("```"):
                result = re.sub(r"^```(?:json)?\s*", "", result)
                result = re.sub(r"\s*```$", "", result)
            parsed = json.loads(result)
            # Clean up triple apostrophes from AI responses (e.g., ''' -> ')
            for key in ["headline", "body"]:
                if key in parsed and isinstance(parsed[key], str):
                    parsed[key] = parsed[key].replace("'''", "'")
            if not parsed.get("body"):
                logger.warning(f"      Empty body in rewrite from {provider} (attempt {attempt+1}), retrying...")
                time.sleep(2)
                continue
            return {
                "headline": parsed.get("headline", source_title),
                "body": parsed.get("body", ""),
                "tags": parsed.get("tags", []),
                "_provider": provider,
            }
        except json.JSONDecodeError as e:
            logger.error(f"JSON decode error (attempt {attempt+1}): {e}\nRaw: {result[:300]}")
            time.sleep(2)
        except Exception as e:
            logger.error(f"AI rewrite error (attempt {attempt+1}): {e}")
            time.sleep(2)
    return None

# ---------------------------------------------------------------------------
# Groq AI — Quality Check
# ---------------------------------------------------------------------------
def quality_check(rewritten, original_title, source_summary=""):
    """Second AI pass to verify quality and factual accuracy against source."""
    headline = rewritten["headline"]
    body = rewritten["body"]
    logger.info(f"QC input: source_summary length={len(source_summary) if source_summary else 0}, source_is_short={len(source_summary) < 200 if source_summary else True}")

    source_is_short = len(source_summary) < 400 if source_summary else True
    word_count = len(body.split())
    min_words = 200 if source_is_short else 250
    if word_count < min_words:
        return False, [f"Article too short ({word_count} words, minimum {min_words})"], "local"
    if word_count > 800:
        return False, [f"Article too long ({word_count} words, maximum 800)"], "local"

    # If source is very brief (just a headline), skip detailed fact-checking
    # and just verify basic quality
    if source_is_short and len(source_summary) < 500:
        logger.info(f"  QC: Source very brief ({len(source_summary)} chars) — using relaxed check")
        # Basic quality checks only
        if len(body.split()) < 150:
            return False, ["Article too short for brief source"], "local"
        if "[INSERT]" in body or "TODO" in body or "lorem ipsum" in body.lower():
            return False, ["Contains placeholder text"], "local"
        if "as an AI" in body.lower() or "i was asked" in body.lower():
            return False, ["Contains AI markers"], "local"
        # Pass basic quality check for brief sources
        return True, [], "local"

    source_context = ""
    if source_summary:
        if source_is_short:
            source_context = f"""

SOURCE TEXT (brief — limited verification possible):
{source_summary}

FACTUAL ACCURACY CHECK (relaxed for short sources):
- Verify that the headline and body are coherent and professional
- Check that no obvious factual errors are present (wrong country, wrong org type)
- If the source mentions a specific event, the article should match that event
- Do NOT fail just because the source is too brief to verify every detail
- Do NOT fail just because the article adds context or background information
- If the rewrite is reasonable and consistent with the brief source, PASS it
- Only FAIL if there are clear factual contradictions with the source
"""
        else:
            source_context = f"""

SOURCE TEXT (the original article summary — verify facts against this):
{source_summary}

FACTUAL ACCURACY CHECK — CRITICAL:
Compare the rewritten article against the source text above. Check:
- Are all dates and timeframes EXACTLY as in the source? (Do NOT accept invented dates)
- Are all organization names EXACTLY as in the source? (Do NOT accept renamed/abbreviated orgs)
- Are all numbers, percentages, and amounts EXACTLY as in the source?
- Are all location names EXACTLY as in the source?
- Are people's names and titles EXACTLY as in the source?
- Has any factual claim been CHANGED or INVENTED compared to the source?
- If the rewritten article contains a specific fact (date, name, number) NOT present in the source, that is a FAIL.

{"IMPORTANT: Source is brief. Focus on obvious factual errors only. Do NOT fail just because the article adds context or background information." if source_is_short else "IMPORTANT: If you cannot verify factual accuracy because the source is too brief, default to FAIL (draft for manual review)."}
"""

    prompt = f"""You are a quality editor for a professional tech news website. Review this rewritten article and check:

1. FACTUAL ACCURACY: Do all facts match the source text? Any invented or altered detail = FAIL. {"[Relaxed: source is brief, focus on obvious errors only]" if source_is_short else ""}
2. COMPLETENESS: Is the article complete with a beginning, middle, and end?
3. COHERENCE: Does it flow well and make logical sense?
4. NO PLACEHOLDERS: Does it contain any placeholder text like [INSERT], TBD, TODO, "lorem ipsum", or error messages?
5. NOT CONTROVERSIAL: Is it free from politically sensitive, controversial, or inflammatory content?
6. NO AI MARKERS: Does it avoid phrases like "as an AI", "I was asked to", "this is a rewrite"?
7. NO INVENTED FACTS: Does the article avoid adding specific details that are NOT in the source?

Original headline: {original_title}
Rewritten headline: {headline}
Body:
{body}
{source_context}

Respond with ONLY a JSON object:
{{"pass": true/false, "issues": ["issue1", "issue2"]}}

{"If the source is brief, focus on obvious errors only. A reasonable rewrite of a brief source should PASS." if source_is_short else "If ANY check fails, or if you CANNOT verify factual accuracy, set 'pass' to false. When in doubt, default to FAIL."}
Set "pass" to true if the article is professional, coherent, and consistent with the available source information."""

    messages = [
        {"role": "system", "content": "You are a quality checker. Respond with valid JSON only."},
        {"role": "user", "content": prompt},
    ]

    for attempt in range(3):
        try:
            result, provider = ai_request(messages, temperature=0.1, max_tokens=1024)
            result = result.strip()
            if not result:
                logger.warning(f"      Empty quality check response from {provider} (attempt {attempt+1})")
                time.sleep(2)
                continue
            if result.startswith("```"):
                result = re.sub(r"^```(?:json)?\s*", "", result)
                result = re.sub(r"\s*```$", "", result)
            parsed = json.loads(result)
            passed = parsed.get("pass", False)
            issues = parsed.get("issues", [])
            return passed, issues, provider
        except (json.JSONDecodeError, Exception) as e:
            logger.error(f"Quality check error from {provider if 'provider' in dir() else '?'} (attempt {attempt+1}): {e}")
            time.sleep(1)
    return False, ["Quality check failed after 3 attempts"], "none"

# ---------------------------------------------------------------------------
# YouTube Search — Data API v3
# ---------------------------------------------------------------------------
def search_youtube(query, max_results=3, recent_days=7):
    """Search YouTube via Data API v3. Returns list of video dicts.
    
    Client-side enforces recency: videos older than recent_days are discarded,
    even if YouTube's publishedAfter filter lets them through.
    Filters out tutorials, courses, and non-news content.
    """
    api_key = CFG.get("youtube", {}).get("api_key", "")
    if not api_key:
        logger.info("  YouTube: no API key configured, skipping video search")
        return []

    # Tutorial/spam keywords to reject
    TUTORIAL_KEYWORDS = [
        "tutorial", "course", "learn", "lesson", "step by step",
        "beginner", "advanced", "masterclass", "workshop", "training",
        "how to", "walkthrough", "crash course", "bootcamp",
        "day 1", "day 2", "day 3", "day 4", "day 5",
        "lecture", "class", "homework", "assignment",
        "certification", "quiz", "mock test",
        "build with", "coding", "code with", "live coding",
        "hackathon", "dev stream", "developer live",
        "python", "javascript", "typescript", "react", "node",
        "simulator", "simulation", "autonomous",
        "free tools", "free ai tools", "channel promotion",
        "subscribe", "ask me anything", "q&a", "q and a",
    ]

    cutoff = None
    params = {
        "part": "snippet",
        "q": query,
        "type": "video",
        "order": "relevance",
        "maxResults": min(max_results * 3, 50),  # over-fetch to compensate for filtering
        "relevanceLanguage": "en",
        "safeSearch": "moderate",
        "key": api_key,
    }

    if recent_days:
        cutoff = datetime.now(timezone.utc) - timedelta(days=recent_days)
        after = cutoff.strftime("%Y-%m-%dT00:00:00Z")
        params["publishedAfter"] = after

    url = f"https://www.googleapis.com/youtube/v3/search?{urllib.parse.urlencode(params)}"

    try:
        req = urllib.request.Request(url, headers={"User-Agent": "iTIN-Pipeline/1.0"})
        with urllib.request.urlopen(req, timeout=15) as resp:
            data = json.loads(resp.read().decode())

        videos = []
        for item in data.get("items", []):
            vid = item.get("id", {})
            if vid.get("kind") != "youtube#video":
                continue
            snippet = item.get("snippet", {})
            video_id = vid.get("videoId", "")
            if not video_id:
                continue

            # Client-side recency enforcement
            published_at = snippet.get("publishedAt", "")
            if cutoff and published_at:
                try:
                    pub_date = datetime.fromisoformat(published_at.replace("Z", "+00:00"))
                    if pub_date < cutoff:
                        logger.debug(f"  YouTube: skipping {video_id} — published {published_at[:10]}, older than {recent_days} days")
                        continue
                except (ValueError, TypeError):
                    logger.warning(f"  YouTube: could not parse date '{published_at}' for {video_id}, skipping")
                    continue

            # Filter out tutorials and non-news content
            title_lower = unescape(snippet.get("title", "")).lower()
            if any(kw in title_lower for kw in TUTORIAL_KEYWORDS):
                logger.debug(f"  YouTube: skipping {video_id} — tutorial/non-news: {title_lower[:50]}")
                continue

            thumbnails = snippet.get("thumbnails", {})
            thumb_url = thumbnails.get("high", thumbnails.get("medium", thumbnails.get("default", {}))).get("url", "")

            videos.append({
                "video_id": video_id,
                "title": unescape(snippet.get("title", "")),
                "url": f"https://www.youtube.com/watch?v={video_id}",
                "thumbnail": thumb_url,
                "channel": snippet.get("channelTitle", ""),
                "published_at": published_at,
            })

            if len(videos) >= max_results:
                break

        logger.info(f"  YouTube API: {len(videos)} recent results for '{query[:50]}' (filtered from {len(data.get('items', []))} total)")
        return videos

    except Exception as e:
        logger.error(f"YouTube API search failed: {e}")
        return []

# ---------------------------------------------------------------------------
# Featured Image — download and set via WP-CLI
def is_image_mostly_one_color(image_path, threshold=0.5):
    """Check if an image is mostly one color (likely a logo/watermark)."""
    try:
        from PIL import Image
        img = Image.open(image_path)
        w, h = img.size
        colors = img.getcolors(maxcolors=100000)
        if colors:
            colors.sort(reverse=True)
            total_pixels = w * h
            top_color_pct = colors[0][0] / total_pixels
            return top_color_pct > threshold
    except Exception:
        pass
    return False

# ---------------------------------------------------------------------------
LOGO_URL_PATTERNS = [
    "logo", "brand", "favicon", "icon", "badge", "avatar",
    "geo.tv", "geo-news", "geo_news", "propakistani", "pro_pakistani",
    "ARY", "samaa", "dunya", "express", "dawn-logo", "tribune-logo",
    "thenews", "app.com.pk", "arynews",
]

def is_likely_logo(image_url, page_url=""):
    """Heuristic: detect if an image URL is a publisher logo rather than article photo."""
    if not image_url:
        return True
    url_lower = image_url.lower()
    for pattern in LOGO_URL_PATTERNS:
        if pattern.lower() in url_lower:
            return True
    # Reject very small image filenames (likely icons/logos)
    filename = url_lower.split("/")[-1].split("?")[0]
    if len(filename) < 8:
        return True
    # Reject images from known logo domains
    if "logo" in url_lower or "icon" in url_lower or "favicon" in url_lower:
        return True
    return False

def set_featured_image(post_id, image_url, article_url, source_url="", category_slug="", conn=None, article_title=""):
    """Download an image and set it as the featured image for a WordPress post.
    
    Hybrid approach:
    1. Try source article's og:image first (real article photo)
    2. Check if it's a logo — if yes, skip
    3. If it's a real photo, use it with credit metadata
    4. Fall back to Unsplash stock photos if source image fails
    """
    wp = CFG["wordpress"]["wp_path"]
    site_root = CFG["wordpress"]["site_root"]
    tmp_path = f"/tmp/wp_featimg_{post_id}.jpg"
    
    # Track image source for credit metadata
    image_source = "stock"  # default
    image_credit = ""

    # Stock photo fallback — topic-based matching (see TOPIC_PHOTOS below)
    
    # HYBRID IMAGE APPROACH: Try source article image first
    # If we have a source URL, try to fetch its og:image
    if source_url and "news.google.com" not in source_url:
        logger.info(f"      Trying source article image from: {source_url[:60]}")
        source_og_image = fetch_og_image(source_url)
        if source_og_image:
            # Check if it's a logo
            if is_likely_logo(source_og_image, source_url):
                logger.info(f"      Source image is a logo, skipping: {source_og_image[:80]}")
            else:
                # Check if it's mostly one color (logo/watermark detection)
                try:
                    check_req = urllib.request.Request(source_og_image, headers={
                        "User-Agent": "Mozilla/5.0 (compatible; iTIN-Pakistan-NewsBot/1.0)",
                        "Referer": source_url,
                    })
                    with urllib.request.urlopen(check_req, timeout=10) as check_resp:
                        check_data = check_resp.read(50000)  # Read partial for check
                        content_type = check_resp.headers.get("Content-Type", "image/jpeg")
                        ext = "jpg"
                        if "png" in content_type: ext = "png"
                        elif "webp" in content_type: ext = "webp"
                        
                        check_path = f"/tmp/wp_featimg_srccheck_{post_id}.{ext}"
                        with open(check_path, "wb") as f:
                            f.write(check_data)
                        
                        if is_image_mostly_one_color(check_path, threshold=0.5):
                            logger.info(f"      Source image is mostly one color (logo), skipping")
                            os.remove(check_path)
                        else:
                            # Source image looks like a real photo — use it
                            image_url = source_og_image
                            image_source = "source"
                            # Extract source name for credit
                            from urllib.parse import urlparse
                            parsed = urlparse(source_url)
                            image_credit = parsed.netloc.replace("www.", "")
                            logger.info(f"      Using source article image: {image_url[:80]}")
                            logger.info(f"      Credit: {image_credit}")
                            if os.path.exists(check_path):
                                os.remove(check_path)
                except Exception as e:
                    logger.debug(f"      Source image check failed: {e}")

    # Topic-specific stock photos — keyed by keyword, NOT by category
    # When an article title contains a keyword, use the matching image pool
    # Each pool has 10-15 diverse images to prevent repetition
    TOPIC_PHOTOS = {
        # Funding / Investment
        "fund": [
            "https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800",
            "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800",
            "https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800",
            "https://images.unsplash.com/photo-1565688534245-05d6b5be184a?w=800",
            "https://images.unsplash.com/photo-1559526324-593bc073d938?w=800",
            "https://images.unsplash.com/photo-1642790106117-e829e14a795f?w=800",
            "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
            "https://images.unsplash.com/photo-1579532537598-459ecdaf39cc?w=800",
            "https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=800",
            "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800",
        ],
        "funding": [
            "https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800",
            "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800",
            "https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800",
            "https://images.unsplash.com/photo-1565688534245-05d6b5be184a?w=800",
            "https://images.unsplash.com/photo-1559526324-593bc073d938?w=800",
            "https://images.unsplash.com/photo-1642790106117-e829e14a795f?w=800",
            "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
            "https://images.unsplash.com/photo-1579532537598-459ecdaf39cc?w=800",
            "https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=800",
            "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800",
        ],
        "investment": [
            "https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800",
            "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800",
            "https://images.unsplash.com/photo-1559526324-593bc073d938?w=800",
            "https://images.unsplash.com/photo-1642790106117-e829e14a795f?w=800",
            "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
            "https://images.unsplash.com/photo-1579532537598-459ecdaf39cc?w=800",
            "https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=800",
            "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800",
            "https://images.unsplash.com/photo-1618044733300-9472054094ee?w=800",
            "https://images.unsplash.com/photo-1518186285589-2f7649de83e0?w=800",
        ],
        "venture": [
            "https://images.unsplash.com/photo-1553729459-efe14ef6055d?w=800",
            "https://images.unsplash.com/photo-1552664730-d307ca884978?w=800",
            "https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800",
            "https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=800",
            "https://images.unsplash.com/photo-1556761175-4b46a572b786?w=800",
            "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800",
            "https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=800",
            "https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800",
        ],
        # Startup / Entrepreneur
        "startup": [
            "https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800",
            "https://images.unsplash.com/photo-1553729459-efe14ef6055d?w=800",
            "https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800",
            "https://images.unsplash.com/photo-1556761175-4b46a572b786?w=800",
            "https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800",
            "https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=800",
            "https://images.unsplash.com/photo-1552664730-d307ca884978?w=800",
            "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800",
            "https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=800",
            "https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800",
            "https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800",
            "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800",
        ],
        "entrepreneur": [
            "https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800",
            "https://images.unsplash.com/photo-1552664730-d307ca884978?w=800",
            "https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800",
            "https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=800",
            "https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=800",
            "https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800",
            "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800",
            "https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=800",
        ],
        # Cloud / Digital
        "cloud": [
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800",
            "https://images.unsplash.com/photo-1480694625550-c2f66f14b3c0?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800",
        ],
        "digital": [
            "https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
            "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
            "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
            "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800",
            "https://images.unsplash.com/photo-1480694625550-c2f66f14b3c0?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
            "https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
        ],
        # Cybersecurity
        "cybersecurity": [
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
            "https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800",
            "https://images.unsplash.com/photo-1550439062-609e1531270e?w=800",
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800",
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
            "https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800",
            "https://images.unsplash.com/photo-1550439062-609e1531270e?w=800",
        ],
        "security": [
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
            "https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800",
            "https://images.unsplash.com/photo-1550439062-609e1531270e?w=800",
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
        ],
        "breach": [
            "https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800",
            "https://images.unsplash.com/photo-1550439062-609e1531270e?w=800",
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1563986768494-4dee2763ff3f?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
            "https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800",
        ],
        # AI / Machine Learning
        "ai": [
            "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=800",
            "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
            "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800",
            "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=800",
        ],
        "artificial intelligence": [
            "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
        ],
        "machine learning": [
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
            "https://images.unsplash.com/photo-1516110833967-0b5716ca1387?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800",
            "https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=800",
            "https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800",
        ],
        # 5G / Telecom
        "5g": [
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
        ],
        "telecom": [
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
            "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800",
            "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800",
        ],
        # Pakistan specific
        "pakistan": [
            "https://images.unsplash.com/photo-1567004054798-ca78d95ac973?w=800",
            "https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=800",
            "https://images.unsplash.com/photo-1567004054798-ca78d95ac973?w=800",
            "https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=800",
            "https://images.unsplash.com/photo-1567004054798-ca78d95ac973?w=800",
            "https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=800",
        ],
        # Review / Phone / Gadget
        "review": [
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800",
        ],
        "phone": [
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
        ],
        "smartphone": [
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800",
            "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800",
        ],
        # Default per category (only used if no keyword matches)
        "_default": [
            "https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=800",
            "https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
            "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800",
            "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
            "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800",
            "https://images.unsplash.com/photo-1480694625550-c2f66f14b3c0?w=800",
            "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800",
            "https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=800",
            "https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=800",
            "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800",
            "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
            "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800",
            "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
        ],
    }

    # Determine which image to use
    use_stock = False
    if not image_url:
        use_stock = True
    elif is_likely_logo(image_url, article_url):
        logger.info(f"      Skipping logo/brand image: {image_url[:80]}")
        use_stock = True

    if use_stock:
        import hashlib
        title_lower = article_title.lower() if article_title else ""

        # Find matching topic from title keywords
        matched_pool = None
        for keyword, pool in TOPIC_PHOTOS.items():
            if keyword.startswith("_"):
                continue
            if keyword in title_lower:
                matched_pool = pool
                logger.info(f"      Matched topic keyword: '{keyword}'")
                break

        # Fallback to default pool
        if not matched_pool:
            matched_pool = TOPIC_PHOTOS["_default"]

        # Use hash to pick different image from the pool each time
        # If conn is available, skip images that were already used
        hash_val = int(hashlib.md5(f"{post_id}-{category_slug}".encode()).hexdigest(), 16)
        start_idx = hash_val % len(matched_pool)

        image_url = None
        if conn:
            # Try to find an unused image starting from the hash position
            for offset in range(len(matched_pool)):
                idx = (start_idx + offset) % len(matched_pool)
                candidate = matched_pool[idx]
                if not is_image_used(conn, candidate):
                    image_url = candidate
                    break
            if not image_url:
                # All images in pool used — pick by hash (will still be unique file on disk)
                logger.warning(f"      All images in pool used for topic, recycling by hash")
                image_url = matched_pool[start_idx]
        else:
            image_url = matched_pool[start_idx]

        logger.info(f"      Using topic stock photo: {image_url[:80]}")

    try:
        # Download image
        req = urllib.request.Request(image_url, headers={
            "User-Agent": "Mozilla/5.0 (compatible; iTIN-Pakistan-NewsBot/1.0)",
            "Referer": article_url,
        })
        with urllib.request.urlopen(req, timeout=15) as resp:
            img_data = resp.read()

        if len(img_data) < 1000:
            logger.warning(f"      Image too small ({len(img_data)} bytes), skipping")
            return False

        # Check if image is mostly one color (logo/watermark)
        content_type = resp.headers.get("Content-Type", "image/jpeg")
        ext = "jpg"
        if "png" in content_type:
            ext = "png"
        elif "webp" in content_type:
            ext = "webp"
        tmp_check_path = f"/tmp/wp_featimg_check_{post_id}.{ext}"
        with open(tmp_check_path, "wb") as f:
            f.write(img_data)
        
        if is_image_mostly_one_color(tmp_check_path, threshold=0.5):
            logger.warning(f"      Skipping logo/watermark image (mostly one color): {image_url[:80]}")
            if os.path.exists(tmp_check_path):
                os.remove(tmp_check_path)
            return False
        if os.path.exists(tmp_check_path):
            os.remove(tmp_check_path)
        tmp_path = f"/tmp/wp_featimg_{post_id}.{ext}"

        with open(tmp_path, "wb") as f:
            f.write(img_data)

        # Import to WordPress and set as featured image
        import_cmd = [
            wp, "media", "import", tmp_path,
            f"--title=iTIN-{post_id}",
            "--porcelain",
            f"--allow-root", f"--path={site_root}",
        ]
        result = subprocess.run(import_cmd, capture_output=True, text=True, timeout=30)
        if result.returncode != 0:
            logger.warning(f"      Media import failed: {result.stderr[:100]}")
            return False

        attachment_id = result.stdout.strip()
        if not attachment_id.isdigit():
            logger.warning(f"      Invalid attachment ID: {attachment_id}")
            return False

        # Set alt text for the image (use article title)
        post_title = ""
        try:
            title_cmd = [wp, "post", "get", str(post_id), "--field=post_title", "--allow-root", f"--path={site_root}"]
            title_result = subprocess.run(title_cmd, capture_output=True, text=True, timeout=10)
            if title_result.returncode == 0:
                post_title = title_result.stdout.strip()
        except Exception:
            pass
        
        if post_title:
            alt_cmd = [
                wp, "post", "meta", "update", str(attachment_id),
                "_wp_attachment_image_alt", post_title,
                f"--allow-root", f"--path={site_root}",
            ]
            subprocess.run(alt_cmd, capture_output=True, text=True, timeout=10)

        # Set as featured image
        set_cmd = [
            wp, "post", "meta", "update", str(post_id), "_thumbnail_id", attachment_id,
            f"--allow-root", f"--path={site_root}",
        ]
        result = subprocess.run(set_cmd, capture_output=True, text=True, timeout=15)
        if result.returncode != 0:
            logger.warning(f"      Set featured image failed: {result.stderr[:100]}")
            return False

        logger.info(f"      Featured image set: attachment_id={attachment_id}")
        
        # Track the image URL to prevent reuse across articles
        if conn and image_url:
            mark_image_used(conn, image_url)
            logger.info(f"      Image tracked for dedup: {image_url[:80]}")
        
        # Store image source credit metadata
        if image_source == "source" and image_credit:
            credit_cmd = [
                wp, "post", "meta", "update", str(post_id),
                "_itinpk_image_credit", image_credit,
                f"--allow-root", f"--path={site_root}",
            ]
            subprocess.run(credit_cmd, capture_output=True, text=True, timeout=10)
            logger.info(f"      Image credit saved: {image_credit}")
        
        return True
    except Exception as e:
        logger.warning(f"      Featured image error: {e}")
        return False
    finally:
        if os.path.exists(tmp_path):
            try:
                os.remove(tmp_path)
            except OSError:
                pass

# ---------------------------------------------------------------------------
# WordPress Publisher — via WP-CLI
# ---------------------------------------------------------------------------
def create_wp_post(article, rewritten, videos, status="publish", conn=None):
    """Create a WordPress post via WP-CLI."""
    wp = CFG["wordpress"]["wp_path"]
    site_root = CFG["wordpress"]["site_root"]
    cat_slug = article["category_slug"]
    category_id = CFG["categories"][cat_slug]["category_id"]
    image_url = article.get("image_url")
    article_url = article.get("url", "")
    headline = rewritten["headline"]
    body = rewritten["body"]
    tags = rewritten.get("tags", [])
    source_url = article.get("source_url", article["url"])
    publisher_name = article.get("source_name", "")

    html_body = body.replace("\n\n", "</p><p>")
    html_body = f"<p>{html_body}</p>"

    if videos:
        vid = videos[0]
        # No manual escaping needed for subprocess.run list args
        html_body += f'\n<div class="embedded-video" data-video-id="{vid["video_id"]}" data-video-title="{vid["title"]}" style="margin:20px 0;text-align:center;">'
        html_body += f'<a href="https://www.youtube.com/watch?v={vid["video_id"]}" class="video-inline-link" data-video-id="{vid["video_id"]}" data-video-title="{vid["title"]}" onclick="return false;">'
        html_body += f'<img src="{vid["thumbnail"]}" alt="{vid["title"]}" style="max-width:100%;border-radius:8px;cursor:pointer;" />'
        html_body += f'</a><p style="font-size:12px;color:#888;">Watch: <a href="https://www.youtube.com/watch?v={vid["video_id"]}" class="video-inline-link" data-video-id="{vid["video_id"]}" data-video-title="{vid["title"]}" onclick="return false;">{vid["title"]}</a></p></div>'

    meta_desc = rewritten["headline"][:155]

    # No manual shell escaping needed — subprocess.run with list handles quoting
    title_escaped = headline
    body_escaped = html_body
    category_escaped = str(category_id)

    cmd = [
        wp, "post", "create",
        f"--post_title={title_escaped}",
        f"--post_content={body_escaped}",
        f"--post_status={status}",
        f"--post_category={category_escaped}",
        "--porcelain",
        "--allow-root",
        f"--path={site_root}",
    ]

    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)
        if result.returncode != 0:
            logger.error(f"WP-CLI create failed: {result.stderr}")
            return None

        post_id = result.stdout.strip()
        logger.info(f"  -> Post created: ID={post_id}, status={status}")

        if tags:
            tag_names = ",".join(tags[:5])
            subprocess.run(
                [wp, "post", "term", "set", post_id, "post_tag", tag_names,
                 f"--allow-root", f"--path={site_root}"],
                capture_output=True, timeout=15,
            )

        if meta_desc:
            subprocess.run(
                [wp, "post", "meta", "update", post_id, "_yoast_wpseo_metadesc", meta_desc,
                 f"--allow-root", f"--path={site_root}"],
                capture_output=True, timeout=15,
            )

        subprocess.run(
            [wp, "post", "meta", "update", post_id, "_yoast_wpseo_title",
             f"{headline} - iTIN PAKISTAN",
             f"--allow-root", f"--path={site_root}"],
            capture_output=True, timeout=15,
        )

        set_featured_image(post_id, image_url, article_url, source_url=article.get("source_url", ""), category_slug=article.get("category_slug", ""), conn=conn, article_title=headline)

        if videos:
            vid = videos[0]
            for meta_key, meta_val in [
                ("_itinpk_video_id", vid["video_id"]),
                ("_itinpk_video_url", f"https://www.youtube.com/watch?v={vid['video_id']}"),
                ("_itinpk_video_title", vid["title"]),
                ("_itinpk_video_thumbnail", vid["thumbnail"]),
            ]:
                # No manual escaping needed for subprocess.run list args
                subprocess.run(
                    [wp, "post", "meta", "update", post_id, meta_key, str(meta_val),
                     f"--allow-root", f"--path={site_root}"],
                    capture_output=True, timeout=15,
                )
            logger.info(f"  -> Video meta set: {vid['video_id']}")

        # Store source URL and publisher name as post meta
        for meta_key, meta_val in [
            ("_itinpk_source_url", source_url),
            ("_itinpk_source_name", publisher_name),
        ]:
            # No manual escaping needed for subprocess.run list args
            subprocess.run(
                [wp, "post", "meta", "update", post_id, meta_key, str(meta_val),
                 f"--allow-root", f"--path={site_root}"],
                capture_output=True, timeout=15,
            )
        logger.info(f"  -> Source meta set: {publisher_name}")

        return post_id
    except subprocess.TimeoutExpired:
        logger.error("WP-CLI command timed out")
        return None
    except Exception as e:
        logger.error(f"WP post creation error: {e}")
        return None

# ---------------------------------------------------------------------------
# Main Pipeline
# ---------------------------------------------------------------------------
def run_pipeline(source="google", custom_query=None):
    logger.info("=" * 60)
    if custom_query:
        logger.info(f"iTIN News Pipeline [{source.upper()}] — Custom query: {custom_query}")
    else:
        logger.info(f"iTIN News Pipeline [{source.upper()}] — Run started at {datetime.now(timezone.utc).isoformat()}")
    logger.info("=" * 60)

    conn = init_db()
    stats = {"fetched": 0, "processed": 0, "published": 0, "drafted": 0, "skipped": 0, "failed": 0, "source": source, "providers": {"groq": 0, "openrouter": 0}}

    categories = CFG["categories"]
    trusted = CFG["trusted_sources"]
    max_per_cat = CFG["pipeline"]["max_articles_per_category"]
    max_total = CFG["pipeline"]["max_total_per_run"]

    all_articles = []
    if custom_query:
        # Custom query mode: fetch once, assign categories by topic relevance
        if source == "newsapi":
            all_articles = fetch_newsapi_articles(
                custom_query, "it-news", "IT News",
                max_total, trusted,
            )
        else:
            all_articles = fetch_rss_articles(
                custom_query, "it-news", "IT News",
                max_total, trusted,
            )
    else:
        # Fetch from Google News RSS for each category
        for slug, cat_cfg in categories.items():
            if source == "newsapi":
                articles = fetch_newsapi_articles(
                    cat_cfg["query"], slug, cat_cfg["category_name"],
                    max_per_cat, trusted,
                )
                # Try alt query if primary returned 0
                if not articles and cat_cfg.get("query_alt"):
                    logger.info(f"  Primary query returned 0, trying alt: {cat_cfg['query_alt']}")
                    articles = fetch_newsapi_articles(
                        cat_cfg["query_alt"], slug, cat_cfg["category_name"],
                        max_per_cat, trusted,
                    )
            else:
                articles = fetch_rss_articles(
                    cat_cfg["query"], slug, cat_cfg["category_name"],
                    max_per_cat, trusted,
                )
                # Try alt query if primary returned 0
                if not articles and cat_cfg.get("query_alt"):
                    logger.info(f"  Primary query returned 0, trying alt: {cat_cfg['query_alt']}")
                    articles = fetch_rss_articles(
                        cat_cfg["query_alt"], slug, cat_cfg["category_name"],
                        max_per_cat, trusted,
                    )
            all_articles.extend(articles)
        
        # Fetch from direct RSS sources (TechJuice, ProPakistani, etc.)
        rss_sources = CFG.get("rss_sources", {})
        for source_key, source_cfg in rss_sources.items():
            if not source_cfg.get("enabled", True):
                continue
            if source_key == "google_news":
                continue  # Already handled above
            
            rss_url = source_cfg.get("url", "")
            category_slug = source_cfg.get("category", "it-news")
            category_name = categories.get(category_slug, {}).get("category_name", "IT News")
            
            if rss_url:
                logger.info(f"Fetching from direct RSS source: {source_key}")
                articles = fetch_direct_rss_articles(
                    rss_url, category_slug, category_name,
                    max_per_cat, trusted, source_key=source_key,
                )
                all_articles.extend(articles)

    # Deduplicate by URL before sorting
    seen_urls = set()
    unique_articles = []
    for a in all_articles:
        if a["url"] not in seen_urls:
            seen_urls.add(a["url"])
            unique_articles.append(a)
    all_articles = unique_articles

    all_articles.sort(key=lambda a: a["score"], reverse=True)
    all_articles = all_articles[:max_total]
    stats["fetched"] = len(all_articles)
    logger.info(f"Total articles to process ({source}): {len(all_articles)}")

    for i, article in enumerate(all_articles):
        url = article["url"]
        normalized_url = normalize_url(url)
        # Use resolved URL as canonical — so dedup works across Google News redirect variants

        if is_url_processed(conn, url) or is_url_processed(conn, normalized_url):
            logger.info(f"  [{i+1}] SKIP (URL duplicate): {article['title'][:60]}")
            stats["skipped"] += 1
            continue

        is_dup, existing_title, similarity = is_title_similar(conn, article["title"])
        if is_dup:
            logger.info(f"  [{i+1}] SKIP (title match {similarity:.0%}): {article['title'][:50]} vs {existing_title[:50]}")
            stats["skipped"] += 1
            continue

        logger.info(f"  [{i+1}] Processing ({source}): {article['title'][:60]}...")

        min_summary = CFG["pipeline"].get("min_summary_length", 80)
        summary_text = article.get("summary", "")
        clean_summary = re.sub(r'<[^>]+>', '', summary_text).strip()
        
        # Skip if summary is too short or just a title (Google News redirect)
        if len(clean_summary) < min_summary or clean_summary.lower() == article["title"].lower():
            logger.info(f"      SKIP (insufficient content: {len(clean_summary)} chars clean text)")
            mark_url(conn, url, article["category_slug"], "insufficient_source", article["title"], source)
            stats["skipped"] += 1
            continue

        # === TOPIC GUARDRAIL: Hard filter BEFORE any rewriting ===
        guard_pass, guard_reason, guard_category = filter_article(
            title=article["title"],
            summary=clean_summary,
            content="",
            source_url=article.get("source_url", ""),
            source_name=article.get("source_name", ""),
        )
        if not guard_pass:
            logger.info(f"      REJECTED (guardrail): {guard_reason}")
            mark_url(conn, url, article["category_slug"], "off_topic", article["title"], source)
            stats["skipped"] += 1
            continue
        # Use guardrail-determined category if it differs from query category
        if guard_category and guard_category != article.get("category_name"):
            logger.info(f"      Guardrail reassigned: {article.get('category_name')} -> {guard_category}")
            article["category_name"] = guard_category
            # Find the matching slug
            for slug, cat_cfg in CFG["categories"].items():
                if cat_cfg["category_name"] == guard_category:
                    article["category_slug"] = slug
                    break

        rewritten = rewrite_article(article)
        if not rewritten or not rewritten.get("body"):
            logger.warning(f"      Rewrite failed or empty — skipping")
            mark_url(conn, url, article["category_slug"], "rewrite_failed", article["title"], source)
            stats["failed"] += 1
            continue

        rewrite_provider = rewritten.get("_provider", "unknown")
        stats["providers"][rewrite_provider] = stats["providers"].get(rewrite_provider, 0) + 1

        if rewritten.get("_political"):
            mark_url(conn, url, article["category_slug"], "political_skipped", article["title"], source)
            stats["skipped"] += 1
            continue

        # Topic relevance check — reject off-topic content
        if not is_topic_relevant(article["title"], article.get("summary", "")):
            logger.info(f"      SKIP (off-topic): {article['title'][:60]}")
            mark_url(conn, url, article["category_slug"], "off_topic", article["title"], source)
            stats["skipped"] += 1
            continue

        passed, issues, qc_provider = quality_check(rewritten, article["title"], article.get("summary", ""))
        logger.info(f"      Quality check ({qc_provider}): {'PASS' if passed else 'FAIL'} {issues if issues else ''}")

        yt_cfg = CFG.get("youtube", {})
        # Add "Pakistan" to search query for Pakistan-focused videos
        search_query = rewritten["headline"]
        if "pakistan" not in search_query.lower():
            search_query = f"{search_query} Pakistan"
        videos = search_youtube(
            search_query,
            max_results=yt_cfg.get("max_results", 3),
            recent_days=yt_cfg.get("recent_days", 3),
        )

        # === VIDEO GUARDRAIL: Filter videos by topic relevance ===
        filtered_videos = []
        for vid in videos:
            vid_pass, vid_reason = is_video_topic_allowed(
                video_title=vid.get("title", ""),
                video_channel=vid.get("channel", ""),
                article_title=rewritten.get("headline", ""),
                article_summary=clean_summary,
            )
            if vid_pass:
                filtered_videos.append(vid)
            else:
                logger.info(f"      Video rejected: {vid.get('title', '')[:50]} — {vid_reason}")
        videos = filtered_videos

        if passed:
            post_id = create_wp_post(article, rewritten, videos, status="publish", conn=conn)
            if post_id:
                mark_url(conn, url, article["category_slug"], "published", rewritten["headline"], source)
                stats["published"] += 1
                stats["processed"] += 1
                logger.info(f"      PUBLISHED ({rewrite_provider}, qc:{qc_provider}): post_id={post_id}")
            else:
                mark_url(conn, url, article["category_slug"], "publish_failed", rewritten["headline"], source)
                stats["failed"] += 1
        else:
            post_id = create_wp_post(article, rewritten, videos, status="draft", conn=conn)
            if post_id:
                mark_url(conn, url, article["category_slug"], "drafted", rewritten["headline"], source)
                stats["drafted"] += 1
                stats["processed"] += 1
                logger.info(f"      DRAFTED ({rewrite_provider}, qc:{qc_provider}): post_id={post_id} (issues: {issues})")
            else:
                mark_url(conn, url, article["category_slug"], "draft_failed", rewritten["headline"], source)
                stats["failed"] += 1

        time.sleep(6)

    conn.close()

    logger.info("=" * 60)
    logger.info(f"Pipeline complete [{source}]. Stats: {json.dumps(stats)}")
    provider_stats = stats.get("providers", {})
    if provider_stats:
        logger.info(f"AI provider usage: {json.dumps(provider_stats)}")
    logger.info(f"Log saved: {log_file}")
    logger.info("=" * 60)

    return stats

# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------
if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="iTIN Pakistan News Pipeline")
    parser.add_argument("--source", choices=["google", "newsapi"], default="google",
                        help="News source: google (RSS) or newsapi (NewsAPI.org)")
    parser.add_argument("--search-query", type=str, default=None,
                        help="Custom search query (overrides category queries)")
    args = parser.parse_args()

    try:
        stats = run_pipeline(source=args.source, custom_query=args.search_query)
        sys.exit(0 if stats["failed"] == 0 else 1)
    except Exception as e:
        logger.exception(f"Pipeline crashed: {e}")
        sys.exit(2)

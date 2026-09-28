#!/usr/bin/env python3
"""
Real-Time Category News Videos — Fetches trending YouTube news videos per category.
Runs every 2-3 hours via cron. Writes JSON consumed by WordPress frontend.
Quota cost: ~1000 units/run (5 categories x 1 search x 100 units)
"""

import json
import logging
import os
import sys
import urllib.request
import urllib.parse
from datetime import datetime, timezone, timedelta
from html import unescape
from pathlib import Path

SCRIPT_DIR = Path("/opt/itinpk-news")
CONFIG_PATH = SCRIPT_DIR / "config.json"
LOG_DIR = SCRIPT_DIR / "logs"
LOG_FILE = LOG_DIR / "category_videos.log"
OUTPUT_DIR = Path("/var/www/itinpk/wp-content/uploads/category-videos")
OUTPUT_FILE = OUTPUT_DIR / "latest_videos.json"

LOG_DIR.mkdir(exist_ok=True)
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler(sys.stdout),
    ],
)
logger = logging.getLogger(__name__)

with open(CONFIG_PATH) as f:
    CFG = json.load(f)

API_KEY = CFG["youtube"]["api_key"]
SEARCH_URL = "https://www.googleapis.com/youtube/v3/search"
VIDEOS_URL = "https://www.googleapis.com/youtube/v3/videos"

# How many videos to fetch per category
VIDEOS_PER_CATEGORY = 5

# Recency: only videos from last N days
RECENT_DAYS = 3

# Category search queries — real news-focused terms
CATEGORY_QUERIES = {
    "it-news": {
        "name": "IT News",
        "slug": "it-news",
        "color": "#0881BE",
        "icon": "IT",
        "queries": [
            "Pakistan technology startup funding",
            "Pakistan 5G telecom digital transformation",
            "Pakistan software IT export",
            "Pakistan tech company launch",
        ],
    },
    "startups-entrepreneurs": {
        "name": "Startups & Entrepreneurs",
        "slug": "startups-entrepreneurs",
        "color": "#37215F",
        "icon": "ST",
        "queries": [
            "Pakistan startup seed funding round",
            "Pakistan startup raising investment",
            "Pakistani entrepreneur building startup",
            "Pakistan venture capital tech fund",
        ],
    },
    "cybersecurity-tech": {
        "name": "Cybersecurity & Tech",
        "slug": "cybersecurity-tech",
        "color": "#e74c3c",
        "icon": "CS",
        "queries": [
            "Pakistan cybersecurity data breach news",
            "Pakistan hacking cyber attack news",
            "Pakistan online privacy security news",
            "Pakistan ransomware malware attack news",
        ],
    },
    "ai-cloud": {
        "name": "AI & Cloud",
        "slug": "ai-cloud",
        "color": "#8e44ad",
        "icon": "AI",
        "queries": [
            "Pakistan artificial intelligence news",
            "Pakistan AI launch news",
            "Pakistan cloud computing data center news",
            "Pakistan machine learning news",
        ],
    },
    "reviews": {
        "name": "Reviews",
        "slug": "reviews",
        "color": "#27ae60",
        "icon": "RV",
        "queries": [
            "latest smartphone review 2025",
            "new phone unboxing review",
            "tech gadget review today",
            "laptop tablet review 2025",
        ],
    },
}

# Tutorial/non-news keywords to reject
REJECT_KEYWORDS = [
    "tutorial", "course", "learn", "lesson", "step by step",
    "beginner", "masterclass", "workshop", "training",
    "how to make", "build with", "coding tutorial",
    "day 1", "day 2", "day 3", "day 4", "day 5",
    "lecture", "class", "homework", "assignment",
    "certification", "quiz", "mock test",
    "free tools", "free ai tools", "channel promotion",
    "subscribe", "ask me anything", "q&a", "q and a",
    "python tutorial", "javascript tutorial", "react tutorial",
    "live coding", "dev stream", "developer live",
    "hackathon", "code along", "code with me",
]

# Political/non-tech keywords to reject from news videos
POLITICAL_KEYWORDS = [
    "imran khan", "pti", "pmln", "pml-n", "ppp", "mqm",
    "parliament", "senate", "national assembly", "election",
    "prime minister", "chief minister", "governor",
    "protest", "dharna", "sit-in", "strike",
    "corruption", "scam", "arrest", "bail", "jail",
    "field marshal", "resignation", "coup",
    "military", "army chief", "ispr",
    "match", "cricket", "football", "psl",
    "bollywood", "hindi", "drama", "actor",
    "gold price", "dollar rate", "petrol price",
    "weather", "earthquake", "flood",
]


def api_get(url, params, timeout=15):
    """Make a YouTube API GET request."""
    full_url = f"{url}?{urllib.parse.urlencode(params)}"
    try:
        req = urllib.request.Request(full_url, headers={"User-Agent": "iTIN-Pipeline/1.0"})
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return json.loads(resp.read().decode())
    except Exception as e:
        logger.error(f"API error: {e}")
        return None


def search_videos(query, max_results=10, recent_days=3):
    """Search YouTube for recent news videos. Returns list of video dicts."""
    cutoff = datetime.now(timezone.utc) - timedelta(days=recent_days)
    after = cutoff.strftime("%Y-%m-%dT00:00:00Z")

    params = {
        "part": "snippet",
        "q": query,
        "type": "video",
        "order": "date",
        "maxResults": min(max_results * 2, 50),
        "relevanceLanguage": "en",
        "safeSearch": "moderate",
        "publishedAfter": after,
        "key": API_KEY,
    }

    data = api_get(SEARCH_URL, params)
    if not data:
        return []

    videos = []
    for item in data.get("items", []):
        vid = item.get("id", {})
        if vid.get("kind") != "youtube#video":
            continue

        video_id = vid.get("videoId", "")
        if not video_id:
            continue

        snippet = item.get("snippet", {})
        title = unescape(snippet.get("title", ""))
        title_lower = title.lower()

        # Reject tutorials and non-news
        if any(kw in title_lower for kw in REJECT_KEYWORDS):
            continue

        # Reject political/non-tech content
        if any(kw in title_lower for kw in POLITICAL_KEYWORDS):
            continue

        # Reject non-English titles (scripts like Devanagari, Korean, Arabic, Malayalam, etc.)
        if any(0x0900 <= ord(c) <= 0x097F for c in title):  # Devanagari (Hindi, Nepali)
            continue
        if any(0x0980 <= ord(c) <= 0x09FF for c in title):  # Bengali
            continue
        if any(0x0A00 <= ord(c) <= 0x0A7F for c in title):  # Gurmukhi (Punjabi)
            continue
        if any(0x0B00 <= ord(c) <= 0x0B7F for c in title):  # Oriya
            continue
        if any(0x0B80 <= ord(c) <= 0x0BFF for c in title):  # Tamil
            continue
        if any(0x0C00 <= ord(c) <= 0x0C7F for c in title):  # Telugu
            continue
        if any(0x0C80 <= ord(c) <= 0x0CFF for c in title):  # Kannada
            continue
        if any(0x0D00 <= ord(c) <= 0x0D7F for c in title):  # Malayalam
            continue
        if any(0xAC00 <= ord(c) <= 0xD7AF for c in title):  # Korean
            continue
        if any(0x0600 <= ord(c) <= 0x06FF for c in title):  # Arabic
            continue
        if any(0x0400 <= ord(c) <= 0x04FF for c in title):  # Cyrillic (Russian)
            continue
        if any(0x0E00 <= ord(c) <= 0x0E7F for c in title):  # Thai
            continue

        # Reject live streams (already live or upcoming)
        if snippet.get("liveBroadcastContent") in ("live", "upcoming"):
            continue

        thumbnails = snippet.get("thumbnails", {})
        thumb = thumbnails.get("high", thumbnails.get("medium", thumbnails.get("default", {}))).get("url", "")

        videos.append({
            "video_id": video_id,
            "title": title,
            "url": f"https://www.youtube.com/watch?v={video_id}",
            "thumbnail": thumb,
            "channel": snippet.get("channelTitle", ""),
            "published_at": snippet.get("publishedAt", ""),
        })

        if len(videos) >= max_results:
            break

    return videos


def get_video_stats(video_ids):
    """Fetch view counts for videos (for sorting by popularity)."""
    if not video_ids:
        return {}

    stats = {}
    for i in range(0, len(video_ids), 50):
        batch = video_ids[i:i+50]
        params = {
            "part": "statistics",
            "id": ",".join(batch),
            "key": API_KEY,
        }
        data = api_get(VIDEOS_URL, params)
        if not data:
            continue

        for item in data.get("items", []):
            vid = item.get("id", "")
            view_count = int(item.get("statistics", {}).get("viewCount", 0))
            stats[vid] = {"views": view_count}

    return stats


def fetch_category(cat_slug, cat_config):
    """Fetch videos for a single category. Returns list of video dicts."""
    all_videos = []
    seen_ids = set()

    queries = cat_config["queries"]
    per_query = max(VIDEOS_PER_CATEGORY // len(queries) + 1, 3)

    for query in queries:
        logger.info(f"  Searching: '{query}'")
        videos = search_videos(query, max_results=per_query, recent_days=RECENT_DAYS)

        for v in videos:
            if v["video_id"] not in seen_ids:
                seen_ids.add(v["video_id"])
                all_videos.append(v)

    # Sort by recency (most recent first)
    all_videos.sort(key=lambda v: v.get("published_at", ""), reverse=True)

    # Trim to target count
    return all_videos[:VIDEOS_PER_CATEGORY]


def main():
    logger.info("=" * 60)
    logger.info("Category News Videos — Fetching real-time YouTube videos")
    logger.info(f"Recency window: {RECENT_DAYS} days")
    logger.info("=" * 60)

    output = {
        "updated_at": datetime.now(timezone.utc).isoformat(),
        "hero": None,
        "categories": {},
    }

    # --- HERO VIDEO: Latest live or most recent tech news video ---
    logger.info("\n--- HERO VIDEO ---")
    hero_queries = [
        "Pakistan technology news today",
        "Pakistan AI artificial intelligence news",
        "Pakistan startup funding news today",
        "Pakistan 5G telecom digital news",
        "Pakistan cybersecurity tech news",
        "Pakistan cloud computing data center news",
    ]
    hero_video = None
    seen_hero = set()
    for hq in hero_queries:
        logger.info(f"  Hero search: '{hq}'")
        vids = search_videos(hq, max_results=5, recent_days=1)
        for v in vids:
            if v["video_id"] not in seen_hero:
                seen_hero.add(v["video_id"])
                # Skip non-English titles (comprehensive script detection)
                t = v["title"]
                if any(0x0900 <= ord(c) <= 0x097F for c in t):  # Devanagari
                    continue
                if any(0x0980 <= ord(c) <= 0x09FF for c in t):  # Bengali
                    continue
                if any(0x0A00 <= ord(c) <= 0x0A7F for c in t):  # Gurmukhi
                    continue
                if any(0x0B80 <= ord(c) <= 0x0BFF for c in t):  # Tamil
                    continue
                if any(0x0C00 <= ord(c) <= 0x0C7F for c in t):  # Telugu
                    continue
                if any(0x0C80 <= ord(c) <= 0x0CFF for c in t):  # Kannada
                    continue
                if any(0x0D00 <= ord(c) <= 0x0D7F for c in t):  # Malayalam
                    continue
                if any(0xAC00 <= ord(c) <= 0xD7AF for c in t):  # Korean
                    continue
                if any(0x0600 <= ord(c) <= 0x06FF for c in t):  # Arabic
                    continue
                if any(0x0400 <= ord(c) <= 0x04FF for c in t):  # Cyrillic
                    continue
                if any(0x0E00 <= ord(c) <= 0x0E7F for c in t):  # Thai
                    continue
                hero_video = v
                break
        if hero_video:
            break

    if hero_video:
        # Get stats for hero video
        stats = get_video_stats([hero_video["video_id"]])
        hero_video["views"] = stats.get(hero_video["video_id"], {}).get("views", 0)
        hero_video.pop("published_at", None)
        output["hero"] = hero_video
        logger.info(f"  Hero: {hero_video['title'][:60]} [{hero_video['channel']}]")
    else:
        logger.warning("  No hero video found")

    total_videos = 0

    for cat_slug, cat_config in CATEGORY_QUERIES.items():
        logger.info(f"\n--- {cat_config['name']} ---")
        videos = fetch_category(cat_slug, cat_config)

        if videos:
            # Get view counts for sorting
            video_ids = [v["video_id"] for v in videos]
            stats = get_video_stats(video_ids)

            # Attach stats and sort by views
            for v in videos:
                v["views"] = stats.get(v["video_id"], {}).get("views", 0)
                # Remove published_at from output (internal use only)
                v.pop("published_at", None)

            videos.sort(key=lambda v: v["views"], reverse=True)

        output["categories"][cat_slug] = {
            "name": cat_config["name"],
            "slug": cat_config["slug"],
            "color": cat_config["color"],
            "icon": cat_config["icon"],
            "videos": videos,
        }

        total_videos += len(videos)
        logger.info(f"  -> {len(videos)} videos saved for {cat_config['name']}")

    # Write output
    with open(OUTPUT_FILE, "w") as f:
        json.dump(output, f, indent=2, ensure_ascii=False)

    logger.info(f"\nTotal: {total_videos} videos + hero across {len(CATEGORY_QUERIES)} categories")
    logger.info(f"Output: {OUTPUT_FILE}")
    logger.info("=" * 60)

    return total_videos


if __name__ == "__main__":
    count = main()

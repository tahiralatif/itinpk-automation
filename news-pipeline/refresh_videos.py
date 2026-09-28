#!/usr/bin/env python3
"""
Video Freshness Refresh — runs every2 hours
Re-checks recent articles and swaps stale videos for fresher ones.
If no fresher video exists, removes the outdated attachment.
"""

import json
import logging
import os
import subprocess
import sys
import urllib.request
import urllib.parse
from datetime import datetime, timezone, timedelta
from pathlib import Path

SCRIPT_DIR = Path("/opt/itinpk-news")
CONFIG_PATH = SCRIPT_DIR / "config.json"
LOG_DIR = SCRIPT_DIR / "logs"
LOG_FILE = LOG_DIR / "video_refresh.log"

LOG_DIR.mkdir(exist_ok=True)

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
WP = CFG["wordpress"]["wp_path"]
SITE_ROOT = CFG["wordpress"]["site_root"]

SEARCH_URL = "https://www.googleapis.com/youtube/v3/search"
VIDEOS_URL = "https://www.googleapis.com/youtube/v3/videos"

# Freshness window — videos older than this get checked for replacement
FRESHNESS_DAYS = 3

# How many recent articles to check (homepage-featured range)
CHECK_ARTICLE_LIMIT = 20


def api_get(url, params):
    full_url = f"{url}?{urllib.parse.urlencode(params)}"
    try:
        req = urllib.request.Request(full_url, headers={"User-Agent": "iTIN-Pipeline/1.0"})
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read().decode())
    except Exception as e:
        logger.error(f"API error: {e}")
        return None


def wp_cmd(args):
    cmd = [WP] + args + [f"--allow-root", f"--path={SITE_ROOT}"]
    result = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
    return result.stdout.strip() if result.returncode == 0 else None


def get_articles_with_videos(limit=20):
    """Get published articles that have videos attached."""
    # First try recent articles
    output = wp_cmd([
        "post", "list", "--post_type=post", "--post_status=publish",
        "--orderby=date", "--order=DESC", f"--posts_per_page={limit}",
        "--fields=ID,post_title,post_date", "--format=json"
    ])
    if not output:
        return []
    
    try:
        posts = json.loads(output)
    except json.JSONDecodeError:
        return []
    
    articles = []
    for post in posts:
        post_id = post["ID"]
        video_id = wp_cmd(["post", "meta", "get", str(post_id), "_itinpk_video_id"])
        if video_id:
            articles.append({
                "id": post_id,
                "title": post["post_title"],
                "date": post["post_date"],
                "video_id": video_id,
            })
    
    # If no recent articles have videos, search ALL articles with videos
    if not articles:
        logger.info("No recent articles with videos — searching all articles...")
        all_output = wp_cmd([
            "post", "list", "--post_type=post", "--post_status=publish",
            "--orderby=date", "--order=DESC", "--posts_per_page=100",
            "--fields=ID,post_title,post_date", "--format=json"
        ])
        if all_output:
            try:
                all_posts = json.loads(all_output)
                for post in all_posts:
                    post_id = post["ID"]
                    video_id = wp_cmd(["post", "meta", "get", str(post_id), "_itinpk_video_id"])
                    if video_id:
                        articles.append({
                            "id": post_id,
                            "title": post["post_title"],
                            "date": post["post_date"],
                            "video_id": video_id,
                        })
            except json.JSONDecodeError:
                pass
    
    return articles


def search_fresh_video(query, recent_days=3):
    """Search YouTube for a fresh video on the topic."""
    params = {
        "part": "snippet",
        "q": query,
        "type": "video",
        "maxResults": 5,
        "key": API_KEY,
        "order": "date",
        "relevanceLanguage": "en",
        "publishedAfter": (datetime.now(timezone.utc) - timedelta(days=recent_days)).isoformat(),
    }
    data = api_get(SEARCH_URL, params)
    if not data:
        return None
    
    items = data.get("items", [])
    if not items:
        return None
    
    # Pick the first (most recent) result
    vid = items[0]
    snippet = vid.get("snippet", {})
    video_id = vid.get("id", {}).get("videoId", "")
    
    if not video_id:
        return None
    
    # Verify the video is actually playable
    v_params = {
        "part": "status,contentDetails",
        "id": video_id,
        "key": API_KEY,
    }
    v_data = api_get(VIDEOS_URL, v_params)
    if v_data and v_data.get("items"):
        status = v_data["items"][0].get("status", {})
        if status.get("uploadStatus") != "processed":
            return None
    
    return {
        "video_id": video_id,
        "title": snippet.get("title", ""),
        "url": f"https://www.youtube.com/watch?v={video_id}",
    }


def refresh_article_video(article):
    """Try to find a fresher video for an article. Returns True if swapped."""
    post_id = article["id"]
    current_video_id = article["video_id"]
    title = article["title"]
    
    # Calculate age of current video
    post_date = datetime.strptime(article["date"], "%Y-%m-%d %H:%M:%S")
    age_days = (datetime.now() - post_date).days
    
    # If article is less than freshness window, skip
    if age_days < FRESHNESS_DAYS:
        logger.info(f"  Post {post_id}: {age_days} days old — within window, skipping")
        return False
    
    logger.info(f"  Post {post_id}: {age_days} days old — checking for fresher video...")
    
    # Search for a fresh video on the same topic
    fresh = search_fresh_video(title, recent_days=FRESHNESS_DAYS)
    
    if fresh and fresh["video_id"] != current_video_id:
        # Found a fresher video — swap it in
        wp_cmd(["post", "meta", "update", str(post_id), "_itinpk_video_id", fresh["video_id"]])
        wp_cmd(["post", "meta", "update", str(post_id), "_itinpk_video_url", fresh["url"]])
        wp_cmd(["post", "meta", "update", str(post_id), "_itinpk_video_title", fresh["title"]])
        
        # Set thumbnail from YouTube
        thumb_url = f"https://img.youtube.com/vi/{fresh['video_id']}/maxresdefault.jpg"
        wp_cmd(["post", "meta", "update", str(post_id), "_itinpk_video_thumbnail", thumb_url])
        
        logger.info(f"    SWAPPED: {current_video_id} -> {fresh['video_id']} ({fresh['title'][:50]})")
        return True
    elif fresh and fresh["video_id"] == current_video_id:
        logger.info(f"    SAME: Current video is still the best match")
        return False
    else:
        # No fresher video found — remove outdated video
        logger.info(f"    REMOVING: No fresher video found, removing stale attachment")
        wp_cmd(["post", "meta", "delete", str(post_id), "_itinpk_video_id"])
        wp_cmd(["post", "meta", "delete", str(post_id), "_itinpk_video_url"])
        wp_cmd(["post", "meta", "delete", str(post_id), "_itinpk_video_title"])
        wp_cmd(["post", "meta", "delete", str(post_id), "_itinpk_video_thumbnail"])
        return True


def main():
    logger.info("=" * 60)
    logger.info("Video Freshness Refresh — checking recent articles")
    logger.info(f"Freshness window: {FRESHNESS_DAYS} days")
    logger.info("=" * 60)
    
    articles = get_articles_with_videos(CHECK_ARTICLE_LIMIT)
    logger.info(f"Found {len(articles)} articles with videos")
    
    swapped = 0
    removed = 0
    kept = 0
    
    for article in articles:
        result = refresh_article_video(article)
        if result is True:
            # Check if video was removed or swapped
            current = wp_cmd(["post", "meta", "get", str(article["id"]), "_itinpk_video_id"])
            if current:
                swapped += 1
            else:
                removed += 1
        else:
            kept += 1
    
    logger.info(f"Results: {swapped} swapped, {removed} removed, {kept} kept")
    logger.info("=" * 60)
    
    return swapped + removed


if __name__ == "__main__":
    count = main()

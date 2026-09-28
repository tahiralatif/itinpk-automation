#!/usr/bin/env python3
"""
YouTube Live Streams Discovery — Credible Tech Shows Only
Strict quality filters: subscriber count, spam detection, credible channels only.
Quota cost: ~1500 units/day (15% of free tier)
"""

import json
import logging
import os
import sys
import urllib.request
import urllib.parse
from datetime import datetime, timezone, timedelta

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(SCRIPT_DIR, "config.json")
OUTPUT_DIR = "/var/www/itinpk/wp-content/uploads/web-shows"
OUTPUT_FILE = os.path.join(OUTPUT_DIR, "upcoming_streams.json")
LOG_DIR = os.path.join(SCRIPT_DIR, "logs")
LOG_FILE = os.path.join(LOG_DIR, "upcoming_streams.log")

os.makedirs(OUTPUT_DIR, exist_ok=True)
os.makedirs(LOG_DIR, exist_ok=True)

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
CHANNELS_URL = "https://www.googleapis.com/youtube/v3/channels"

# ---------------------------------------------------------------------------
# QUALITY FILTERS
# ---------------------------------------------------------------------------

# Minimum subscriber count to be considered credible
MIN_SUBSCRIBERS = 50000

# Known credible tech channels (whitelist — bypass subscriber check)
CREDIBLE_CHANNELS = {
    # Major tech news
    "mkbhd", "marques brownlee", "linus tech tips", "the verge",
    "techcrunch", "engadget", "wired", "cnet", "mrwhosetheboss",
    "dave2d", "dave lee", "shortcircuit", "austin evans",
    # AI / Cloud
    "two minute papers", "ai explained", "sentence power",
    "freecodecamp", "fireship", "the ai advantage",
    # Cybersecurity
    "cyberspatial", "networkchuck", "professor messer",
    "the cyber mentor", "john hammond", "layer5",
    # Startups / Business
    "y combinator", "how to start a startup", "this is product management",
    "the sbi", "my first million",
    # Pakistan-specific credible channels
    "dawn news", "geo news", "ary news", "tech pvt ltd",
    "pakistani tech", "propakistani", "tech juice",
    # Tech conferences / organizations
    "cncf", "cloud native", "aws", "google cloud", "microsoft",
    "apple", "tesla", "spacex", "nasa",
}

# Spam / clickbait title patterns — REJECT immediately
SPAM_TITLE_PATTERNS = [
    # Self-promotion
    "channel promotion", "channel checking", "channel review",
    "free tools", "free ai tools", "free giveaway",
    "subscribe", "sub4sub", "subscribe to",
    # Clickbait
    "you won't believe", "this is insane", "mind blowing",
    "100% free", "limited time", "act now",
    # Low-quality streams
    "live q&a", "live q and a", "ask me anything",
    "chill stream", "hangout", "just chatting",
    "music stream", "lofi stream", "study stream",
    "sleep stream", "rain sounds", "ambient",
    # Generic spam
    "live channel", "going live", "i'm live",
    "testing stream", "test stream", "first live",
    "welcome to my channel", "new channel",
    # Non-tech content (even from big channels)
    "krishna", "janmashtami", "temple", "mandir", "prayer", "worship",
    "christmas", "easter", "ramadan", "eid", "diwali", "holi",
    "football", "soccer", "cricket", "tennis", "basketball", "nfl", "nba",
    "election", "parliament", "senate", "congress",
    "forex", "trading", "stock market", "share market", "crypto",
    "gold price", "silver price", "oil price",
    "weather", "storm", "hurricane", "tornado",
    "recipe", "cooking", "baking", "food",
    "movie", "film", "hollywood", "bollywood",
    "music", "song", "album", "concert",
]

# Channels that are NEVER credible — even if they have subscribers
BLOCKED_CHANNELS = [
    # Tutorial / Educational (not news)
    "nextwork", "devtown", "gamerz bench", "ml classes",
    "focus flow", "work music", "24x7 stream",
    "self-host hub", "major seifer",
    # Music / Entertainment
    "defected records", "synthesis pulse", "chili ai music",
    "art of electronic", "devs fm",
    # Gaming
    "gaming", "esports", "streamer",
    # Religious
    "full gospel", "church", "mosque", "temple",
    # Generic small channels
    "tech tips hindi", "tech guru", "tech master",
    "ai world", "ai tools", "ai master",
    # General news (not tech-specific)
    "ndtv", "republic world", "times now", "india today",
    "aaj tak", "zee news", "news18",
    "fox news", "cnn", "bbc news", "sky news",
    "bloomberg television", "cnbc", "et now",
    "wion", "dawn news", "geo news", "ary news",
    "mathrubhumi", "janam tv", "livenow from fox",
    "republic", "times now", "india today",
    "fox", "cnn", "msnbc", "al jazeera",
    # Forex / Trading (not tech)
    "forex", "trading", "stock market",
]


def api_get(url, params, timeout=15):
    full_url = f"{url}?{urllib.parse.urlencode(params)}"
    try:
        req = urllib.request.Request(full_url, headers={"User-Agent": "iTIN-Pipeline/1.0"})
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return json.loads(resp.read().decode())
    except Exception as e:
        logger.error(f"API error: {e}")
        return None


def is_spam_title(title):
    """Check if title contains spam/clickbait patterns."""
    title_lower = title.lower()
    return any(pattern in title_lower for pattern in SPAM_TITLE_PATTERNS)


def is_blocked_channel(channel_name):
    """Check if channel is in blocklist."""
    channel_lower = channel_name.lower()
    return any(blocked in channel_lower for blocked in BLOCKED_CHANNELS)


def is_credible_channel(channel_name):
    """Check if channel is in known credible whitelist."""
    channel_lower = channel_name.lower()
    return any(credible in channel_lower for credible in CREDIBLE_CHANNELS)


def get_channel_stats(channel_ids):
    """Fetch subscriber counts for channels. Returns dict of channel_id -> stats."""
    if not channel_ids:
        return {}
    
    stats = {}
    # Process in batches of50 (API limit)
    for i in range(0, len(channel_ids), 50):
        batch = channel_ids[i:i+50]
        params = {
            "part": "statistics",
            "id": ",".join(batch),
            "key": API_KEY,
        }
        data = api_get(CHANNELS_URL, params)
        if not data:
            continue
        
        for item in data.get("items", []):
            cid = item.get("id", "")
            subs = int(item.get("statistics", {}).get("subscriberCount", 0))
            videos = int(item.get("statistics", {}).get("videoCount", 0))
            stats[cid] = {
                "subscribers": subs,
                "video_count": videos,
            }
    
    return stats


def get_video_details(video_ids):
    """Fetch live/upcoming status and channel IDs for videos."""
    if not video_ids:
        return {}
    
    result = {}
    for i in range(0, len(video_ids), 50):
        batch = video_ids[i:i+50]
        params = {
            "part": "liveStreamingDetails,contentDetails,snippet",
            "id": ",".join(batch),
            "key": API_KEY,
        }
        data = api_get(VIDEOS_URL, params)
        if not data:
            continue
        
        for item in data.get("items", []):
            vid = item.get("id", "")
            details = item.get("liveStreamingDetails", {})
            content = item.get("contentDetails", {})
            snippet = item.get("snippet", {})
            result[vid] = {
                "scheduled_start": details.get("scheduledStartTime", ""),
                "actual_start": details.get("actualStartTime", ""),
                "actual_end": details.get("actualEndTime", ""),
                "live_broadcast": details.get("liveBroadcastContent", ""),
                "duration": content.get("duration", ""),
                "concurrent_viewers": details.get("concurrentViewers", ""),
                "channel_id": snippet.get("channelId", ""),
                "channel_title": snippet.get("channelTitle", ""),
            }
    
    return result


def search_streams(query, event_type, max_results=10):
    """Search YouTube for live/upcoming streams."""
    params = {
        "part": "snippet",
        "q": query,
        "type": "video",
        "eventType": event_type,
        "maxResults": max_results,
        "key": API_KEY,
        "order": "date",
        "relevanceLanguage": "en",
    }
    data = api_get(SEARCH_URL, params)
    if not data:
        return []

    results = []
    for item in data.get("items", []):
        snippet = item.get("snippet", {})
        video_id = item.get("id", {}).get("videoId", "")
        title = snippet.get("title", "")
        channel = snippet.get("channelTitle", "")
        channel_id = snippet.get("channelId", "")
        description = snippet.get("description", "")
        published_at = snippet.get("publishedAt", "")
        thumb = snippet.get("thumbnails", {}).get("high", {}).get("url", "")
        if not thumb:
            thumb = snippet.get("thumbnails", {}).get("medium", {}).get("url", "")

        if not video_id or not title:
            continue

        results.append({
            "video_id": video_id,
            "url": f"https://www.youtube.com/watch?v={video_id}",
            "title": title,
            "channel": channel,
            "channel_id": channel_id,
            "description": description[:200],
            "thumbnail": thumb,
            "published_at": published_at,
        })

    return results


def main():
    logger.info("=" * 60)
    logger.info("YouTube Live Streams Discovery — Credible Tech Shows Only")
    logger.info(f"Minimum subscribers: {MIN_SUBSCRIBERS:,}")
    logger.info("=" * 60)

    all_streams = []
    seen_ids = set()

    # Tech-specific search queries — focused on genuine tech content
    SEARCH_QUERIES = [
        {"q": "technology news live stream", "category": "IT News", "type_label": "Tech News"},
        {"q": "artificial intelligence live stream", "category": "AI & Cloud", "type_label": "AI"},
        {"q": "cybersecurity news live stream", "category": "Cybersecurity", "type_label": "Cyber"},
        {"q": "startup pitch live stream", "category": "Startups", "type_label": "Startup"},
        {"q": "cloud computing conference live", "category": "AI & Cloud", "type_label": "Cloud"},
        {"q": "tech conference live stream", "category": "IT News", "type_label": "Conference"},
    ]

    for sq in SEARCH_QUERIES:
        # Search for currently live streams
        logger.info(f"Searching LIVE: '{sq['q']}' [{sq['category']}]")
        streams = search_streams(sq["q"], "live", max_results=8)
        for s in streams:
            if s["video_id"] not in seen_ids:
                seen_ids.add(s["video_id"])
                s["discovered_category"] = sq["category"]
                s["type_label"] = sq["type_label"]
                s["event_type"] = "live"
                s["scheduled_display"] = "Happening Now"
                all_streams.append(s)

        # Search for upcoming scheduled streams
        logger.info(f"Searching UPCOMING: '{sq['q']}' [{sq['category']}]")
        streams = search_streams(sq["q"], "upcoming", max_results=8)
        for s in streams:
            if s["video_id"] not in seen_ids:
                seen_ids.add(s["video_id"])
                s["discovered_category"] = sq["category"]
                s["type_label"] = sq["type_label"]
                s["event_type"] = "upcoming"
                s["scheduled_display"] = ""
                all_streams.append(s)

    logger.info(f"Raw results before quality filter: {len(all_streams)}")

    # -----------------------------------------------------------------------
    # PHASE1: Spam title filter (cheapest — no API calls)
    # -----------------------------------------------------------------------
    filtered = []
    for s in all_streams:
        if is_spam_title(s["title"]):
            logger.info(f"  [SPAM] {s['title'][:60]}")
            continue
        if is_blocked_channel(s["channel"]):
            logger.info(f"  [BLOCKED CHANNEL] {s['channel']}")
            continue
        filtered.append(s)
    logger.info(f"After spam/channel filter: {len(filtered)}")

    # -----------------------------------------------------------------------
    # PHASE2: Fetch video details (verify live/expired, get channel IDs)
    # -----------------------------------------------------------------------
    video_ids = [s["video_id"] for s in filtered]
    details = get_video_details(video_ids) if video_ids else {}

    valid = []
    for s in filtered:
        vid = s["video_id"]
        info = details.get(vid, {})

        # Skip expired streams
        if info.get("actual_end"):
            logger.info(f"  [EXPIRED] {s['title'][:60]}")
            continue

        # Update channel_id from video details
        if info.get("channel_id"):
            s["channel_id"] = info["channel_id"]

        # Verify live status
        if info.get("actual_start") or info.get("live_broadcast") == "live":
            s["event_type"] = "live"
            s["scheduled_display"] = "Happening Now"
            s["viewers"] = info.get("concurrent_viewers", "")
        elif s["event_type"] == "live" and not info.get("actual_start"):
            # Search said live but API doesn't confirm — keep but note
            pass

        # Format upcoming schedule
        if s["event_type"] == "upcoming" and info.get("scheduled_start"):
            try:
                dt = datetime.fromisoformat(info["scheduled_start"].replace("Z", "+00:00"))
                now = datetime.now(timezone.utc)
                diff = dt - now
                if diff.total_seconds() < 0:
                    if info.get("actual_start") or info.get("live_broadcast") == "live":
                        s["event_type"] = "live"
                        s["scheduled_display"] = "Happening Now"
                    else:
                        continue
                elif diff.days > 0:
                    s["scheduled_display"] = dt.strftime("Starts %b %d at %I:%M %p UTC")
                elif diff.seconds > 3600:
                    hours = diff.seconds // 3600
                    s["scheduled_display"] = f"Starts in {hours}h"
                else:
                    mins = diff.seconds // 60
                    s["scheduled_display"] = f"Starts in {mins}m"
                s["scheduled_at"] = info["scheduled_start"]
            except Exception:
                s["scheduled_display"] = "Scheduled"

        valid.append(s)

    logger.info(f"After expiry/live verification: {len(valid)}")

    # -----------------------------------------------------------------------
    # PHASE3: Subscriber count filter (most expensive — batch channel lookups)
    # -----------------------------------------------------------------------
    # Collect unique channel IDs
    channel_ids = list(set(s.get("channel_id", "") for s in valid if s.get("channel_id")))
    
    if channel_ids:
        logger.info(f"Fetching stats for {len(channel_ids)} channels...")
        channel_stats = get_channel_stats(channel_ids)
        
        filtered_final = []
        for s in valid:
            cid = s.get("channel_id", "")
            channel_name = s.get("channel", "")
            
            # Whitelist bypass — known credible channels
            if is_credible_channel(channel_name):
                s["subscribers"] = channel_stats.get(cid, {}).get("subscribers", 0)
                filtered_final.append(s)
                logger.info(f"  [WHITELIST] {channel_name} ({s.get('subscribers', 0):,} subs)")
                continue
            
            # Check subscriber count
            stats = channel_stats.get(cid, {})
            subs = stats.get("subscribers", 0)
            s["subscribers"] = subs
            
            if subs >= MIN_SUBSCRIBERS:
                filtered_final.append(s)
                logger.info(f"  [PASS] {channel_name} ({subs:,} subs)")
            else:
                logger.info(f"  [REJECTED] {channel_name} ({subs:,} subs < {MIN_SUBSCRIBERS:,})")
        
        valid = filtered_final
    
    logger.info(f"After subscriber filter: {len(valid)}")

    # -----------------------------------------------------------------------
    # PHASE4: Sort and save
    # -----------------------------------------------------------------------
    def sort_key(s):
        if s["event_type"] == "live":
            return (0, "")
        return (1, s.get("scheduled_at", "9999"))
    valid.sort(key=sort_key)

    # Don't overwrite if rate-limited and found nothing
    if len(valid) == 0 and os.path.exists(OUTPUT_FILE):
        try:
            with open(OUTPUT_FILE) as f:
                existing = json.load(f)
            if existing.get("count", 0) > 0:
                logger.warning(f"Zero quality results — keeping existing {existing['count']} streams")
                return existing["count"]
        except Exception:
            pass

    # Clean up internal fields before saving
    for s in valid:
        s.pop("channel_id", None)
        s.pop("description", None)
        s.pop("published_at", None)

    output = {
        "updated_at": datetime.now(timezone.utc).isoformat(),
        "count": len(valid),
        "streams": valid,
    }

    with open(OUTPUT_FILE, "w") as f:
        json.dump(output, f, indent=2, ensure_ascii=False)

    live_count = sum(1 for s in valid if s["event_type"] == "live")
    upcoming_count = sum(1 for s in valid if s["event_type"] == "upcoming")
    logger.info(f"Saved {len(valid)} streams ({live_count} live, {upcoming_count} upcoming)")
    logger.info("=" * 60)
    return len(valid)


if __name__ == "__main__":
    count = main()

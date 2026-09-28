# iTIN Pakistan News Pipeline — Reliability & Maintenance Guide

## Overview
Automated news pipeline that fetches articles, rewrites via AI, and publishes to WordPress.
- **Google News RSS**: Every 30 minutes
- **NewsAPI.org**: Every 4 hours (0, 4, 8, 12, 16, 20 UTC)
- **Daily Digest Email**: 9 AM UTC (2 PM PKT)

## Critical Issues & Failure Modes

### 1. NewsAPI Rate Limiting (CURRENTLY ACTIVE)
- **Problem**: Free tier allows 100 requests/day
- **Impact**: 429 errors block all NewsAPI articles
- **Current schedule**: 6 runs/day × 5 queries = 30 requests/day (safe)
- **If exceeded**: NewsAPI returns 429, pipeline falls back to Google News only
- **Resolution**: Wait for daily reset at midnight UTC
- **Monitoring**: Check `logs/cron.log` for "HTTP Error 429"

### 2. Google News RSS Provides Only Titles
- **Problem**: Google News RSS `summary` field contains redirect URL, not article content
- **Impact**: Articles with only titles are skipped (correct behavior)
- **Workaround**: NewsAPI provides real summaries (when not rate-limited)
- **Alternative**: Add paid news API (e.g., NewsAPI paid plan, GNews API)

### 3. AI Provider Rate Limits
- **Groq**: 200K tokens/day, 8K tokens/minute (free tier)
- **OpenRouter**: Fallback when Groq is rate-limited
- **Impact**: If both rate-limited, rewrites fail
- **Monitoring**: Check logs for "rate limited, trying fallback"

### 4. Quality Check Failures
- **Problem**: Short source text → AI invents facts → QC rejects
- **Current fix**: Relaxed QC for short sources, skips articles with <120 chars
- **Impact**: Some articles may be drafted instead of published
- **Resolution**: Review drafted articles manually

### 5. YouTube API Quota
- **Limit**: 100 search calls/day (free tier)
- **Current usage**: ~10-20 calls/day (safe)
- **If exceeded**: Video search skipped, articles published without videos

## API Keys & Expiration

| Service | Key | Free Tier | Expiration |
|---------|-----|-----------|------------|
| Groq | gsk_exsL... | 200K TPD | Never (free) |
| OpenRouter | sk-or-v1-... | Varies by model | Never (free) |
| NewsAPI | 647b9bc... | 100 req/day | Never (free) |
| YouTube | AIzaSyB... | 100 searches/day | Never (free) |

## Monitoring Commands

```bash
# Check latest pipeline logs
tail -50 /opt/itinpk-news/logs/cron.log

# Check specific run log
ls -lt /opt/itinpk-news/logs/run_*.log | head -5

# Check published posts
wp post list --post_type=post --post_status=publish --path=/var/www/itinpk --allow-root

# Check draft posts (need review)
wp post list --post_type=post --post_status=draft --path=/var/www/itinpk --allow-root

# Check dedup database
sqlite3 /opt/itinpk-news/processed_urls.db "SELECT status, COUNT(*) FROM processed_urls GROUP BY status;"

# Test NewsAPI connectivity
python3 -c "import urllib.request; urllib.request.urlopen('https://newsapi.org/v2/everything?q=test&apiKey=647b9bc77d6c4debb4dd4b493944b646')"
```

## Manual Operations

### Force a pipeline run
```bash
cd /opt/itinpk-news && python3 news_pipeline.py --source google
cd /opt/itinpk-news && python3 news_pipeline.py --source newsapi
```

### Clear dedup database (allow reprocessing)
```bash
sqlite3 /opt/itinpk-news/processed_urls.db "DELETE FROM processed_urls;"
```

### Publish a draft post
```bash
wp post update <POST_ID> --post_status=publish --path=/var/www/itinpk --allow-root
```

## Maintenance Schedule

- **Daily**: Check `logs/cron.log` for errors
- **Weekly**: Review draft posts, publish good ones
- **Monthly**: Check API quotas, rotate keys if needed
- **As needed**: Update `config.json` for new categories or sources

## Known Limitations

1. **No full article scraping**: Google News RSS doesn't provide content; NewsAPI is rate-limited
2. **No image generation**: Uses OG images from source; fallback to placeholders
3. **No duplicate detection across sources**: Same article from Google + NewsAPI may be published twice
4. **No sentiment analysis**: Political filter is keyword-based only
5. **No A/B testing**: Headlines are AI-generated without optimization

# iTIN Pakistan - Tech News Portal

Pakistan's Premier Tech News Portal — Automated news pipeline + WordPress theme

**Live Site:** [https://itinpk.14.jugaar.ai](https://itinpk.14.jugaar.ai)

## Overview

iTIN Pakistan is a fully automated tech news portal that curates, rewrites, and publishes IT/startup/AI/cybersecurity news from trusted sources. The system runs 24/7 with minimal human intervention.

## Architecture

```
itinpk-automation/
├── news-pipeline/          # Python automation engine
│   ├── news_pipeline.py    # Main pipeline — fetch, rewrite, publish
│   ├── topic_guardrails.py # Content filtering & categorization
│   ├── fetch_category_videos.py  # YouTube video curation
│   ├── refresh_videos.py   # Video freshness updates
│   ├── daily_digest.py     # Daily email digest
│   ├── config_loader.py    # Configuration utilities
│   ├── config.json         # API keys, categories, RSS sources
│   └── .env                # Environment variables
│
└── wordpress-theme/        # GeneratePress child theme
    ├── functions.php       # Custom functions, video system, schema
    ├── header.php          # Two-tier header with mobile drawer
    ├── footer.php          # Footer with newsletter form
    ├── home.php            # Homepage — hero video + category grids
    ├── single.php          # Article layout with trending sidebar
    ├── category-*.php      # Category-specific layouts
    ├── custom-colors.css   # Brand colors & design system
    └── style.css           # Theme metadata
```

## Features

### News Pipeline
- **Multi-source aggregation** — Google News RSS, NewsAPI, direct RSS feeds (TechJuice, ProPakistani, TechCrunch)
- **AI-powered rewriting** — Groq/OpenRouter LLM for headline + summary generation
- **Smart categorization** — 5 categories with topic guardrails and keyword matching
- **Pakistan relevance filter** — Ensures content is Pakistan-focused
- **Auto featured images** — Source OG images with logo detection fallback to Unsplash stock photos
- **Deduplication** — URL normalization + title similarity matching
- **Video embedding** — Automatic YouTube video association per article

### WordPress Theme
- **Custom header** — Two-tier design (utility bar + main nav) with mobile drawer
- **Homepage** — Hero video player + trending headlines + category grids
- **Category pages** — Magazine-style layout with hero, just-in strip, and article grid
- **Article pages** — Two-column layout with trending sidebar
- **Video system** — Category video strips + per-article video embeds
- **SEO** — Yoast integration, schema markup, Open Graph tags
- **Newsletter** — MailPoet integration for email subscriptions
- **Bookmarks** — User article bookmarking system

## Categories

| Category | Color | Description |
|----------|-------|-------------|
| IT News | `#0881BE` | IT infrastructure, telecom, 5G, digital transformation |
| Startups & Entrepreneurs | `#37215F` | Funding, venture capital, founder stories |
| Cybersecurity & Tech | `#e74c3c` | Data breaches, hacking, privacy, compliance |
| AI & Cloud | `#8e44ad` | Artificial intelligence, cloud computing, ML |
| Reviews | `#27ae60` | Smartphone, gadget, and tech product reviews |

## Setup

### Prerequisites
- Python 3.8+
- WordPress with GeneratePress theme
- API keys: Groq, OpenRouter, YouTube Data API, NewsAPI

### News Pipeline
```bash
cd news-pipeline
pip install feedparser requests beautifulsoup4 pillow
cp .env.example .env  # Add your API keys
python news_pipeline.py
```

### WordPress Theme
1. Upload `wordpress-theme/` to `wp-content/themes/`
2. Activate "GeneratePress Child" in WordPress admin
3. Configure Yoast SEO settings
4. Set up MailPoet for newsletters

### Cron Jobs
```bash
# News pipeline — every 30 minutes
*/30 * * * * /usr/bin/python3 /opt/itinpk-news/news_pipeline.py

# Video refresh — every 2 hours
0 */2 * * * /usr/bin/python3 /opt/itinpk-news/fetch_category_videos.py

# Daily digest — once daily at 8 AM
0 8 * * * /usr/bin/python3 /opt/itinpk-news/daily_digest.py
```

## Tech Stack

- **Backend:** Python 3, WordPress, MySQL
- **AI:** Groq (GPT-OSS-20B), OpenRouter (MiniMax-M3)
- **Theme:** GeneratePress, Custom CSS/JS
- **Integrations:** YouTube API, NewsAPI, MailPoet, Plausible Analytics

## License

Private — iTIN Pakistan Technology Network

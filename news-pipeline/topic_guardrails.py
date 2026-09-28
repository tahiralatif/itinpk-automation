#!/usr/bin/env python3
"""
iTIN Pakistan — Topic Guardrails
Hard content filter: only IT/tech/AI/cloud/cybersecurity/startups/reviews allowed.
Applied BEFORE any content is rewritten or published.
"""

import re

# ===========================================================================
# ALLOWED TOPICS — only these are permitted on the site
# ===========================================================================
ALLOWED_TOPICS = {
    "it_news": {
        "keywords": [
            "it ", "information technology", "software", "hardware", "telecom",
            "telecommunication", "5g", "4g", "broadband", "internet", "wifi",
            "digital infrastructure", "data center", "data centre", "server",
            "network", "bandwidth", "fiber optic", "fibre optic", "semiconductor",
            "chip", "processor", "cpu", "gpu", "memory", "storage",
            "operating system", "linux", "windows", "macos", "android", "ios",
            "programming", "coding", "developer", "devops", "cloud",
            "database", "api", "saas", "paas", "iaas", "platform",
            "router", "switch", "firewall", "vpn", "encryption",
            "blockchain", "crypto", "cryptocurrency", "bitcoin", "ethereum",
            "digital payment", "fintech", "mobile wallet", "easypaisa",
            "jazzcash", "sadapay", "nayapay", "nft", "defi",
            "ecommerce", "e-commerce", "online marketplace", "digital commerce",
            " pakistan tech", "pakistan it", "pakistani tech", "pakistani it",
            "karachi tech", "lahore tech", "islamabad tech",
            "moitt", "pseb", "ptcl", "nayatel", "systems limited",
            "netflow", "trg", "aviation", "atel", "callcenter", "bpo",
            "it export", "it minister", "tech minister", "digital pakistan",
            "google", "microsoft", "apple", "amazon", "meta",
            "spectrum", "auction", "telecom",
        ],
        "category": "IT News",
    },
    "ai_cloud": {
        "keywords": [
            "artificial intelligence", " ai ", "machine learning", "deep learning",
            "neural network", "natural language", "nlp", "computer vision",
            "chatbot", "chatgpt", "openai", "google ai", "meta ai", "microsoft ai",
            "large language model", "llm", "generative ai", "agentic ai",
            "automation", "robot", "robotics", "drone", "autonomous",
            "data science", "analytics", "predictive", "recommendation",
            "cloud computing", "cloud service", "cloud infrastructure",
            "aws", "azure", "google cloud", "alibaba cloud",
            "data center", "data centre", "edge computing", "serverless",
            "microservices", "kubernetes", "docker", "container",
            "ai model", "ai tool", "ai agent", "ai platform",
            "training model", "inference", "gpu computing", "tpu",
            "ai startup", "ai funding", "ai investment",
            "pakistan ai", "pakistani ai", "ai pakistan",
        ],
        "category": "AI & Cloud",
    },
    "cybersecurity": {
        "keywords": [
            "cybersecurity", "cyber security", "information security",
            "data breach", "hack", "hacking", "hacker", "malware",
            "ransomware", "phishing", "ddos", "ansomware", "exploit",
            "vulnerability", "zero-day", "patch", "firewall", "intrusion",
            "surveillance", "surveillance", "privacy", "data protection",
            "encryption", "decryption", "authentication", "authorization",
            "penetration testing", "security audit", "security policy",
            "cyber attack", "cyber threat", "cyber crime", "cyber law",
            "digital forensics", "incident response", "security operations",
            "siem", "soc", "threat intelligence", "malware analysis",
            "pakistan cyber", "pakistani cyber", "nccia", "fia cyber",
            "cyber policy", "digital security", "online safety",
        ],
        "category": "Cybersecurity & Tech",
    },
    "startups": {
        "keywords": [
            "startup", "start-up", "startups", "entrepreneur", "entrepreneurship",
            "funding", "seed round", "series a", "series b", "series c",
            "venture capital", "vc ", "vcf", "angel investor", "investment",
            "incubator", "accelerator", "co-working", "coworking",
            "founder", "ceo", "cto", "co-founder", "bootstrapped",
            "pivot", "scaleup", "scale-up", "unicorn", "decacorn",
            "ipo", "exit", "acquisition", "merger",
            "launch", "product launch", "beta", "mvp",
            "pitch", "demo day", "hackathon", "innovation",
            "digital startup", "tech startup", "saas startup",
            "pakistan startup", "pakistani startup", "startup pakistan",
            "ignite", "secp", "venture fund", "startup fund",
            "karachi startup", "lahore startup", "islamabad startup",
        ],
        "category": "Startups & Entrepreneurs",
    },
    "reviews": {
        "keywords": [
            "review", "reviewed", "reviewing", "hands-on", "first look",
            "unboxing", "benchmark", "performance test", "speed test",
            "camera test", "battery test", "display test",
            "pros and cons", "verdict", "rating", "score",
            "comparison", "vs ", "versus", "head-to-head",
            "smartphone", "phone", "tablet", "laptop", "desktop",
            "watch", "earbuds", "headphones", "speaker", "camera",
            "gadget", "device", "accessory", "peripheral",
            "software", "app", "application", "tool", "service",
            "operating system", "browser", "antivirus", "vpn service",
            "samsung", "apple", "iphone", "ipad", "macbook", "pixel",
            "oneplus", "xiaomi", "realme", "oppo", "vivo", "huawei",
            "nokia", "sony", "lg", "dell", "hp", "lenovo", "asus",
            "acer", "msi", "razer", "google pixel", "nothing phone",
            "tech review", "product review", "gadget review",
            "phone review", "laptop review", "tablet review",
        ],
        "category": "Reviews",
    },
}

# ===========================================================================
# BLOCKED TOPICS — immediate rejection, no exceptions
# ===========================================================================
BLOCKED_KEYWORDS = [
    # Entertainment
    "entertainment", "drama", "anime", "movie", "movies", "film", "films",
    "tv show", "television", "netflix", " series ", "episode", "season",
    "celebrity", "actor", "actress", "singer", "musician", "band",
    "concert", "festival", "award show", "oscar", "grammy", "emmy",
    "bollywood", "hollywood", "lollywood", "ollywood",
    "celebrity gossip", "tabloid", "scandal", "affair",
    "reality show", "talent show", "game show", "talk show",
    "youtube drama", "influencer drama", "beef", "feud",

    # General news (non-tech)
    "crime", "murder", "killing", "robbery", "theft", "assault", "arrest",
    "accident", "crash", "disaster", "flood", "earthquake", "cyclone",
    "weather", "forecast", "temperature", "rainfall",
    "election", "ballot", "vote", "voting", "polling", "constituency",
    "parliament", "senate", "assembly", "legislature",
    "political party", "politician",
    "corruption", "bribery", "embezzlement",
    "protest", "rally", "demonstration", "strike",
    "war ", "conflict", "ceasefire", "peace talks", "diplomacy",
    "foreign policy", "bilateral", "multilateral",
    "terrorist", "terrorism", "extremist", "extremism",
    "border dispute", "loc ", "line of control",

    # Health (non-tech)
    "health", "medical", "hospital", "doctor", "patient", "disease",
    "pandemic", "epidemic", "outbreak", "vaccination",
    "cancer", "diabetes", "heart disease", "stroke",
    "mental health", "depression", "anxiety",

    # Sports
    "sports", "cricket", "football", "soccer", "hockey", "tennis",
    "player", "match", "tournament", "championship", "league",
    "icc", "fifa", "olympics", "world cup", "psl", "ipl",
    "batsman", "bowler", "goal", "penalty",

    # Lifestyle/General
    "fashion", "beauty", "makeup", "skincare", "haircut",
    "food", "recipe", "cooking", "restaurant", "cafe",
    "travel", "tourism", "hotel", "flight", "airport",
    "real estate", "property", "housing", "rent", "mortgage",
    "wedding", "marriage", "divorce", "relationship",
    "astrology", "horoscope", "zodiac", "tarot",
    "climate change", "global warming", "environment", "pollution",
    "agriculture", "farming", "crop", "harvest",
]

# ===========================================================================
# Core filter functions
# ===========================================================================

def normalize_text(text):
    """Normalize text for comparison."""
    t = text.lower().strip()
    t = re.sub(r'[^\w\s]', ' ', t)
    t = re.sub(r'\s+', ' ', t)
    return t

# ===========================================================================
# CATEGORY EXCLUSIONS — articles matching these are NOT allowed in target
# ===========================================================================
CATEGORY_EXCLUSIONS = {
    "startups": {
        # These keywords make an article IT News, NOT Startups
        "exclude_if_no": [
            "startup", "start-up", "startups", "entrepreneur", "entrepreneurship",
            "funding", "seed round", "series a", "series b", "series c",
            "venture capital", "vc ", "angel investor", "incubator", "accelerator",
            "founder", "ceo", "cto", "co-founder", "bootstrapped", "unicorn",
            "scaleup", "scale-up", "ipo", "exit", "acquisition",
            "pitch", "demo day", "hackathon", "innovation",
            "pakistan startup", "pakistani startup", "startup pakistan",
            "ignite", "secp", "venture fund", "startup fund",
        ],
        # If article has THESE but NOT the above, it's NOT a startup article
        "reclassify_to": "IT News",
    },
    "it_news": {
        # No exclusions for IT News — it's the default category for tech articles
        "exclude_if_no": [],
        "reclassify_to": None,
    },
    "cybersecurity": {
        "exclude_if_no": [
            "cybersecurity", "cyber security", "data breach", "hack", "malware",
            "ransomware", "phishing", "vulnerability", "zero-day", "firewall",
            "cyber attack", "cyber threat", "cyber crime", "cyber law",
            "online safety", "data protection", "encryption", "privacy",
            "nccia", "fia cyber", "cyber policy", "digital security",
        ],
        "reclassify_to": "IT News",  # Cyber articles without cyber keywords → IT News
    },
}

def is_topic_allowed(title, summary="", content=""):
    """
    Check if article content is about an allowed topic.
    Returns (allowed: bool, reason: str, matched_category: str or None)
    """
    text = normalize_text(f"{title} {summary} {content}")

    # First check BLOCKED topics — immediate rejection
    for keyword in BLOCKED_KEYWORDS:
        if keyword.lower() in text:
            return False, f"Blocked topic detected: '{keyword}'", None

    # Then check ALLOWED topics
    best_match = None
    best_score = 0

    for topic_key, topic_config in ALLOWED_TOPICS.items():
        score = 0
        matched_keywords = []
        for keyword in topic_config["keywords"]:
            if keyword.lower() in text:
                score += 1
                matched_keywords.append(keyword)

        if score > best_score:
            best_score = score
            best_match = (topic_key, topic_config["category"], matched_keywords)

    if not best_match or best_score < 1:
        return False, "No allowed topic matched", None

    matched_key, matched_category, matched_kws = best_match

    # Apply category-specific exclusions
    if matched_key in CATEGORY_EXCLUSIONS:
        exclusions = CATEGORY_EXCLUSIONS[matched_key]
        exclude_keywords = exclusions.get("exclude_if_no", [])
        if exclude_keywords:
            has_required = any(kw.lower() in text for kw in exclude_keywords)
            if not has_required:
                reclassify = exclusions.get("reclassify_to")
                if reclassify:
                    # Try to find the reclassify target category
                    for tk, tc in ALLOWED_TOPICS.items():
                        if tc["category"] == reclassify:
                            return True, f"Excluded from {matched_key} (no relevant keywords), reclassified to {reclassify}", reclassify
                    return False, f"Excluded from {matched_key}: no required keywords found", None
                else:
                    return False, f"Excluded from {matched_key}: no required keywords found", None

    return True, f"Matched {matched_key} ({best_score} keywords)", matched_category

def is_video_topic_allowed(video_title, video_channel="", article_title="", article_summary=""):
    """
    Check if a YouTube video is about an allowed topic AND relevant to Pakistan.
    A video can only be attached if its content is genuinely about allowed topics
    and has clear relevance to a Pakistani tech audience.
    """
    video_text = normalize_text(f"{video_title} {video_channel}")
    combined_text = normalize_text(f"{video_title} {video_channel} {article_title} {article_summary}")

    # Check blocked topics in video
    for keyword in BLOCKED_KEYWORDS:
        if keyword.lower() in video_text:
            return False, f"Video blocked topic: '{keyword}'"

    # Check if video matches any allowed topic
    video_allowed = False
    for topic_key, topic_config in ALLOWED_TOPICS.items():
        for keyword in topic_config["keywords"]:
            if keyword.lower() in video_text:
                video_allowed = True
                break
        if video_allowed:
            break

    if not video_allowed:
        return False, "Video not about any allowed topic"

    # Pakistan relevance check: video or article must mention Pakistan/Pakistani
    pakistan_keywords = [
        "pakistan", "pakistani", "karachi", "lahore", "islamabad", "pindi",
        "ptcl", "jazz", "telenor", "ufone", "zong", "nayatel", "systems limited",
        "seed ventures", "lumi labs", "google pakistan", "meta pakistan",
        "ignite", "naya paisa", "sadapay", "nayapay", "finja", "safepay",
        "bykea", "rozee.pk", "daraz", "telemart", "airblue", "air sial",
        "pia", "cpec", "digital pakistan", "make in pakistan", "made in pakistan",
        "secp", "sbp", "pta", "moitt", "pseb", "caret", "ntc",
    ]
    
    has_pakistan_relevance = any(kw.lower() in combined_text for kw in pakistan_keywords)
    
    if not has_pakistan_relevance:
        return False, "Video not relevant to Pakistan tech audience"

    # The video is about an allowed topic AND relevant to Pakistan
    return True, "Video topic allowed"

def filter_article(title, summary="", content="", source_url="", source_name=""):
    """
    Main filter function. Returns (pass: bool, reason: str, category: str or None).
    Called BEFORE any content is rewritten.
    Articles should be Pakistan-focused or clearly relevant to Pakistan's tech audience.
    """
    # Empty content check
    if not title or len(title.strip()) < 10:
        return False, "Title too short or empty", None

    # Topic relevance check
    allowed, reason, category = is_topic_allowed(title, summary, content)

    if not allowed:
        return False, reason, None

    # Pakistan relevance check (soft — prefer Pakistan-focused but allow relevant global)
    combined_text = normalize_text(f"{title} {summary} {content}")
    pakistan_keywords = [
        "pakistan", "pakistani", "karachi", "lahore", "islamabad", "pindi",
        "ptcl", "jazz", "telenor", "ufone", "zong", "nayatel", "systems limited",
        "seed ventures", "lumi labs", "google pakistan", "meta pakistan",
        "ignite", "naya paisa", "sadapay", "nayapay", "finja", "safepay",
        "bykea", "rozee.pk", "daraz", "telemart", "airblue", "air sial",
        "pia", "cpec", "digital pakistan", "make in pakistan", "made in pakistan",
        "secp", "sbp", "pta", "moitt", "pseb", "caret", "ntc",
        "crypto", "cryptocurrency", "bitcoin", "ethereum", "blockchain",
        "fintech", "digital payment", "mobile wallet", "easypaisa", "jazzcash",
    ]
    
    has_pakistan_relevance = any(kw.lower() in combined_text for kw in pakistan_keywords)
    
    if not has_pakistan_relevance:
        # Hard filter: reject non-Pakistan articles
        # Only allow truly globally significant tech news (major product launches, etc.)
        global_tech_keywords = [
            "apple", "google", "microsoft", "amazon", "meta", "openai",
            "iphone", "android", "windows", "chatgpt", "gpt",
            "semiconductor", "chip shortage", "nvidia", "tesla",
            "techcrunch", "startup", "funding", "venture capital",
            "ai", "artificial intelligence", "machine learning",
            "cloud", "data center", "cybersecurity",
        ]
        is_global_tech = any(kw.lower() in combined_text for kw in global_tech_keywords)
        if not is_global_tech:
            return False, "Not relevant to Pakistan tech audience", None
        return True, f"{reason} (global tech, allowed)", category

    return True, f"{reason} (Pakistan-relevant)", category

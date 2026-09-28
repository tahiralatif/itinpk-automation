# Brevo Domain Authentication - DNS Records Required

## Status: BLOCKED - Requires Cloudflare Dashboard Access

## What Needs to Be Done

### Step 1: Log into Cloudflare
1. Go to https://dash.cloudflare.com
2. Select the `jugaar.ai` domain

### Step 2: Add DNS Records
Go to DNS > Records and add these records:

#### SPF Record (TXT)
- **Type:** TXT
- **Name:** @
- **Content:** `v=spf1 include:spf.mailbrevo.com ~all`
- **Proxy:** DNS only (gray cloud)

#### DKIM Record (TXT)
- **Type:** TXT
- **Name:** `mailbrevo._domainkey`
- **Content:** (Get from Brevo dashboard > Settings > Sender Protection > DKIM)

#### DMARC Record (TXT)
- **Type:** TXT
- **Name:** `_dmarc`
- **Content:** `v=DMARC1; p=none; rua=mailto:tara378581@gmail.com`

### Step 3: Verify in Brevo
1. Go to Brevo Dashboard > Settings > Sender Protection
2. Click "Add a domain"
3. Enter `itinpk.14.jugaar.ai`
4. Brevo will show the DNS records to add
5. Add them to Cloudflare as shown above
6. Click "Verify" in Brevo

### Step 4: Test Email Delivery
After DNS propagation (may take up to 24 hours):
1. Send a test email from WordPress
2. Check Brevo dashboard for delivery status

## Current Issue
Emails from `tara378581@gmail.com` via Brevo are being rejected because the sending domain is not verified.

## Important Notes
- DNS propagation can take up to 24 hours
- Make sure to set Proxy to "DNS only" (gray cloud) for all email-related records
- Never proxy email records through Cloudflare (orange cloud breaks email)

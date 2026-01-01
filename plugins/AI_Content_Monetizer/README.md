# AI Content Monetizer

## Features
- **AI-Powered Content Generation**: Creates exclusive premium content for members using mock AI (integrate OpenAI for pro).
- **Paywall Protection**: Locks content behind login or pro upgrade.
- **Affiliate Integration**: Automatically embeds affiliate links and custom coupons in generated content.
- **Shortcode Support**: Use `[ai_premium_content]` to add premium sections.
- **Freemium Model**: Free version teases, pro unlocks unlimited generations.
- **Analytics Ready**: Track member engagement for upselling.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Configure in **Settings > AI Monetizer**.

## Setup
1. Enter your **Pro Version Key** (purchase at example.com for $49/year).
2. Add **OpenAI API Key** for real AI (optional, mock used otherwise).
3. Input **Affiliate Links** as JSON: `[{{"name":"Product","url":"https://aff.link","coupon":"SAVE20"}}]`.
4. Create a members-only page/post with `[ai_premium_content]` shortcode.
5. Use membership plugin like MemberPress to restrict access.

## Usage
- **For Visitors**: See paywall teaser → Direct to upgrade/login.
- **For Pro Members**: Click **Generate Premium AI Content** button, enter topic (e.g., "best WordPress plugins"), get instant exclusive content with deals.
- **Monetize**: Earn from pro sales, affiliates in content, or sponsored coupons.
- **Example Output**:
  > In-depth guide on WordPress monetization: ... **Special Deal:** Check out this [Product](link) with coupon SAVE20!

## Pro Upgrade
Unlock unlimited AI, custom domains, priority support. Visit example.com/pricing.
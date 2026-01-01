# Smart Affiliate AutoLinker

**Automatically turns keywords in your posts into profitable Amazon affiliate links.**

## Features
- **Auto-Linking**: Detects keywords like "iphone" or "laptop" and replaces them with your Amazon affiliate links.
- **Customizable**: Add unlimited keywords and URLs via settings.
- **SEO-Friendly**: Links open in new tab with `nofollow`.
- **Post-Specific Disable**: Checkbox to skip auto-linking on specific posts.
- **Freemium**: Free core features; **Pro** adds analytics, multiple networks, A/B testing ($49/year).

## Installation
1. Upload the `smart-affiliate-autolinker` folder to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Affiliate AutoLinker** to configure.

## Setup
1. Enter your **Amazon Affiliate ID** (e.g., `yourname-20`).
2. Add keywords in JSON: `{"iphone":"https://amazon.com/dp/B0ABC123?tag=yourname-20","laptop":"https://amazon.com/dp/B0DEF456?tag=yourname-20"}`.
3. Save. Links auto-appear in posts!

**Disable on post**: Edit post > Custom Fields > Add `saal_disable` = `true`.

## Usage
- Write normally: Mention "best iphone" → auto-links to Amazon.
- Earn commissions on sales.
- Track via Amazon Associates dashboard.
- **Pro**: View click stats, support for ClickBank, Commission Junction.

## FAQ
**Safe?** Yes, regex is word-boundary only, no over-linking.
**Conflicts?** Works with most themes/editors; test on staging.

**Upgrade to Pro:** [Get Pro](https://example.com/pro) for full power!

**Support:** [Contact](mailto:support@example.com)
# Smart Affiliate AutoLinker

**Automatically turns your content keywords into profitable affiliate links.** Boost revenue from Amazon Associates and more without manual linking.

## Features

- **Auto-Linking**: Detects keywords (e.g., "WordPress", "plugin") in posts/pages and replaces with your affiliate links.
- **Easy Setup**: Comma-separated keywords and JSON links in settings.
- **SEO-Friendly**: Adds `nofollow sponsored` attributes.
- **Freemium Model**: Free for basics; Pro ($49/year) adds unlimited keywords, analytics dashboard, custom networks, A/B testing, and priority support.
- **Self-Contained**: Single PHP file, no dependencies.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin: **Plugins > Add New > Upload Plugin**.
3. Activate the plugin.
4. Go to **Settings > Affiliate AutoLinker** to configure.

## Setup

1. **Add Keywords**: Enter comma-separated terms (e.g., `WordPress,plugin,theme`).
2. **Add Links**: Use JSON format, e.g.:
   
   {
     "0": {"url": "https://amazon.com/your-affiliate-link1", "text": "Buy Now"},
     "1": {"url": "https://amazon.com/your-affiliate-link2", "text": "Check It Out"}
   }
   
   Indices match keyword positions (0 for first keyword, etc.).
3. Save settings. Links auto-apply to new/existing content.

## Usage

- Write content naturally; plugin handles linking.
- Test on a staging site first with your real affiliate IDs.
- Monitor earnings via your affiliate dashboard.
- **Pro Tip**: Target high-commission keywords for max profit.

## Pro Version

Upgrade for:
- Unlimited keywords & networks (Amazon, ClickBank, etc.).
- Click analytics & performance reports.
- Link cloaking & rotation.
- [Buy Pro Now](https://example.com/pro)

## Support

- Free version: WordPress.org forums.
- Pro: Dedicated email/tickets.

## Changelog

- **1.0.0**: Initial release with auto-linking core.
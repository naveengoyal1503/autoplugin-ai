# Affiliate AutoLink Pro

**Automatically monetize your WordPress site by converting keywords into affiliate links.**

## Features

- **Smart Auto-Linking**: Detects keywords in posts/pages and replaces them with your affiliate links.
- **Configurable Limits**: Set max links per post to avoid over-optimization.
- **SEO-Friendly**: Adds nofollow and opens links in new tabs by default.
- **Easy Admin Panel**: Add/edit keywords and URLs via simple settings page.
- **Freemium Ready**: Free version limited to 5 keywords; Pro unlocks unlimited + analytics.

## Installation

1. Upload the `affiliate-autolink-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' screen in WordPress admin.
3. Go to **Settings > AutoLink Pro** to configure keywords and affiliate URLs.
4. Start publishing content – links auto-generate!

## Setup

1. In the settings page, add keyword-affiliate pairs (e.g., "WordPress" → `https://your-affiliate-link.com/ref`).
2. Adjust max links (default: 3 per post), nofollow, and target blank options.
3. Save changes. Test on a post to verify.

**Pro Tip**: Use high-converting affiliate programs like Amazon Associates, ShareASale, or CJ Affiliate[1][3].

## Usage

- Write normally; plugin scans and links keywords automatically.
- Works on posts/pages (singular views only).
- Avoids linking inside existing `<a>` tags.
- **Upgrade to Pro** for click tracking, A/B testing, custom CSS, and priority support ($49/year).

## FAQ

**Does it work with Gutenberg?** Yes, filters `the_content` universally.

**Performance impact?** Minimal – runs on frontend only, efficient regex.

**Pro Benefits**: Unlimited keywords, performance reports, link rotation[1][2][3].

## Changelog

**1.0.0**
- Initial release.
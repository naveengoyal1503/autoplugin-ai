# Smart Affiliate AutoInserter

**Automatically monetize your WordPress site by inserting relevant Amazon affiliate links into content using smart keyword matching.**

## Features

- **Automatic Link Insertion**: Scans posts and pages for keywords and replaces them with your Amazon affiliate links.
- **Customizable Keywords & Products**: Define your own keywords and corresponding Amazon product URLs.
- **Safe & Compliant**: Adds `nofollow` and `sponsored` attributes for full disclosure.
- **Limits Links**: Max 3 links per post to avoid spamming.
- **Easy Admin Panel**: Configure everything from WordPress Settings > Affiliate Inserter.
- **Freemium Ready**: Pro version unlocks AI-powered context analysis, performance analytics, A/B testing, and premium support.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Upload and activate.
4. Go to **Settings > Affiliate Inserter** to configure your Amazon Affiliate ID, keywords, and product links.

## Setup

1. Sign up for [Amazon Associates](https://affiliate-program.amazon.com/) and get your affiliate tag (e.g., `yourid-20`).
2. In the plugin settings:
   - Enter your **Affiliate ID**.
   - Add **Keywords** (comma-separated, e.g., `laptop,phone,book`).
   - Define **Products** as JSON: `{"laptop":"https://amazon.com/dp/B08N5WRWNW?tag=yourid-20","phone":"https://amazon.com/dp/B0C7V4L2NH?tag=yourid-20"}`.
3. Save settings. Links will auto-insert on published content.

## Usage

- Write content with your keywords naturally.
- The plugin scans and inserts links automatically (e.g., "I love my new **laptop**" becomes a linked affiliate term).
- Test on a staging site first.
- Monitor earnings in your Amazon Associates dashboard.

## Pro Version

Upgrade for:
- AI semantic matching (beyond keywords).
- Link click analytics & heatmaps.
- Custom link styles & positions.
- WooCommerce integration.
- Priority support.

**Pricing: $49/year single site, $99/year bundle (unlimited sites).**

## FAQ

**Does it work with Gutenberg/Classic Editor?** Yes, filters `the_content`.

**Safe for SEO?** Yes, proper rel attributes and limited insertions.

**Support?** Free version: GitHub issues. Pro: Email support.

## Changelog

**1.0.0** - Initial release.
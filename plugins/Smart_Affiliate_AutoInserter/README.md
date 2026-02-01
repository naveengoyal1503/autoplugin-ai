# Smart Affiliate AutoInserter

**Automatically insert Amazon affiliate links into your content for passive income.**

## Features
- **Keyword-based auto-insertion**: Matches keywords in posts/pages to Amazon products.
- **Customizable**: Add your affiliate tag, keywords, and ASINs via simple JSON.
- **Limits control**: Set max links per post to avoid over-insertion.
- **SEO-friendly**: Adds `nofollow sponsored` attributes.
- **Freemium**: Free core; Pro adds AI product suggestions, analytics, WooCommerce integration.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Affiliate Inserter** to configure.

## Setup
1. Enter your **Amazon Affiliate Tag** (e.g., `yourid-20`).
2. Add keywords and ASINs in JSON format: `{"phone":"B08N5WRWNW","laptop":"B09G9FPGT6"}`.
3. Set **Max links per post** (default: 3).
4. Save and test on a post.

## Usage
- Write content with keywords (e.g., "best phone").
- Plugin auto-links on frontend (single posts only).
- Example output: `Check this <a href="...">phone</a><sup>*</sup>`.

**Pro Features**: AI keyword analysis, performance dashboard, unlimited products ($49/year).

## Support
Report issues at [support@example.com](mailto:support@example.com).
# Smart Affiliate Link Cloaker

**Boost your affiliate earnings with automatic link cloaking, tracking, and analytics!**

## Features

- **Auto-Cloaking**: Automatically detects and cloaks affiliate links (Amazon, ClickBank, etc.) in posts, pages, and widgets.
- **Click Tracking**: Tracks every click with basic statistics (free version shows top 20 links).
- **Shortcode Support**: Use `[afflink url="https://example.com/aff-link"]` for manual cloaking.
- **Pretty Links**: Converts links to `/go/unique-slug/` for better UX and branding.
- **Link Protection**: Prevents hijacking and hides ugly affiliate parameters.
- **Freemium**: Free for basics; Pro ($29/year) adds A/B testing, conversion tracking, geo-stats, and unlimited links.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Flush permalinks: **Settings > Permalinks > Save Changes**.
4. Start adding affiliate links to your content—they auto-cloak!

## Setup

- No configuration needed for auto-cloaking.
- View stats: Visit `yoursite.com/?sal_stats`.
- Customize affiliate domains in `wp-config.php` or via filter `sal_aff_domains`.
- Add manual links: `[afflink url="YOUR_AFFILIATE_URL"]`. 

## Usage

1. Write content with raw affiliate links (e.g., Amazon URLs).
2. Publish—links auto-convert to branded `/go/xxxxxx/`.
3. Track clicks at `?sal_stats`.
4. **Pro Tip**: Use tiered pricing psychology like $9.99/month subscriptions for your own memberships alongside affiliates[1][4].

## Pro Upgrade

Unlock advanced features: Detailed analytics, A/B link testing, email capture on clicks. Visit example.com/pro.

## Support

Report issues on WordPress.org forums. Pro users get priority email support.
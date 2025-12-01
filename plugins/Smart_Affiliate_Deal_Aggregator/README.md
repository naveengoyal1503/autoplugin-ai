# Smart Affiliate Deal Aggregator

## Description
Automatically aggregates and displays affiliate coupon codes, deals, and discounts from multiple JSON feeds to boost affiliate revenue for niche blogs and e-commerce sites.

## Features
- Aggregates multiple external affiliate deal feeds.
- Caches deal data to improve performance.
- Displays deals via shortcode `[sada_deal_list count=5]`.
- Admin settings to configure deal feed URLs.
- Clean, responsive deal list display.
- Freemium base, with potential pro upgrades for filtering, link cloaking, and priority updates.

## Installation
1. Upload the plugin PHP file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Smart Affiliate Deals** to add your deal feed URLs (one per line).

## Setup
- Provide JSON feeds with deals, each deal item should have:
  - `title` (string)
  - `link` (string)
  - `affiliate_link` (string) – the tracked affiliate URL
  - `description` (string)
- Save settings.
- Use shortcode in posts, pages, or widgets.

## Usage
- Place `[sada_deal_list count=10]` shortcode where you want to display up to 10 latest deals.
- Configure feeds in admin panel for your preferred sources.

## Notes
- Feeds must be publicly accessible JSON arrays.
- Caching interval is one hour by default for performance.
- Extend the plugin to add premium filtering or link cloaking if needed.

## Support
For support, open an issue on the plugin repository or contact the developer.

---
# Affiliate GeoBoost

A WordPress plugin that dynamically adds geographically targeted affiliate links and coupon codes to your posts and pages to increase affiliate revenue by offering location-specific deals.

## Features

- Detect visitor country using Cloudflare header or IP lookup
- Insert custom affiliate link and coupon based on visitor location
- Default fallback link for unspecified countries
- Simple setup, automatic content injection
- Lightweight with inline styles

## Installation

1. Upload `affiliate-geoboost.php` to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.

## Setup

- The plugin uses predefined affiliate links and coupons for different countries inside the code.
- To customize offers, edit the `$affiliate_links` array in the main plugin file to add or modify country codes, URLs, and coupons.

## Usage

- Simply write your blog posts or pages as usual.
- The plugin will automatically append a targeted affiliate offer box at the end of your content.
- No shortcode or widget needed.

## Monetization

Consider offering a free version with basic geo-targeting for several countries, and a premium upgrade with:

- Advanced analytics (click tracking by region)
- Schedule rotating affiliate links based on time or promotions
- Manage multiple affiliate campaigns
- Customizable design templates

## Support

For support or feature requests, please contact the developer.
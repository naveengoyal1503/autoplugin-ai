# Affiliate Deal Tracker

## Description
Affiliate Deal Tracker automatically aggregates and displays affiliate coupons, deals, and discount codes relevant to your site's niche. This helps website owners increase affiliate conversions by providing visitors with timely, curated savings offers.

## Features
- Displays a curated list of active affiliate deals and coupons
- Shortcode `[affiliate_deals]` to show deals anywhere on your site
- Automatic filtering of expired deals
- Simple styling for seamless integration
- Easy to extend with your own affiliate sources or API integrations

## Installation
1. Upload the `affiliate-deal-tracker.php` file to your WordPress site's `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the shortcode `[affiliate_deals]` in posts, pages, or widget areas where you want deals to appear.

## Setup
Currently, the plugin uses built-in sample deals. To extend:
- Modify the `fetch_deals()` method to fetch your affiliate offers via APIs or RSS
- Replace URLs and referral IDs with your own affiliate links

## Usage
- Insert `[affiliate_deals]` shortcode in any post or page
- The plugin will display a styled list of current deals
- Visitors clicking the deals are redirected through your affiliate URLs

Optimize your affiliate marketing strategy by providing your audience with valuable, updated deals effortlessly using Affiliate Deal Tracker.
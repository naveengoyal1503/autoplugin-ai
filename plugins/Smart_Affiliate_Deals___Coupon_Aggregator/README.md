# Smart Affiliate Deals & Coupon Aggregator

## Description
Smart Affiliate Deals & Coupon Aggregator is a WordPress plugin that fetches, caches, and displays affiliate coupons and deals from multiple merchants. Automatically update deals daily and monetize your website via affiliate commissions seamlessly.

## Features
- Automatic aggregation of affiliate coupons and deals (mocked with sample data in this version)
- Cache deals to improve performance; cache refresh button in admin
- Simple shortcode `[sad_deals]` to display current deals anywhere
- Clean, minimal, and responsive deal listing output
- Admin page for cache status and manual refresh

## Installation
1. Upload the plugin PHP file to your WordPress `/wp-content/plugins/` directory.
2. Activate `Smart Affiliate Deals & Coupon Aggregator` from the Plugins menu in WordPress.
3. Visit the **Affiliate Deals** menu to refresh deals cache initially.

## Setup
- The plugin automatically caches some sample affiliate deals on activation.
- Use the admin page under **Affiliate Deals** to manually refresh the cache.
- Add the shortcode `[sad_deals]` to any post or page where you want to display deals.

## Usage
- Insert `[sad_deals]` shortcode in posts, pages, or widgets to show the current offers.
- Use the admin settings page to refresh deals anytime new offers become available.

*Note: This version uses static example deals; extend it to integrate real API feeds from your affiliate programs for live data.*
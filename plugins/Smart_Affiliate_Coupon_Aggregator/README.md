# Smart Affiliate Coupon Aggregator

A WordPress plugin that aggregates affiliate coupons in a simple JSON format and displays them in a conversion-optimized table with automatic coupon code copying.

## Features

- Easy coupon management using a JSON input area in admin
- Coupon display with title, code, expiration date, and clickable button
- Automatic coupon code copy to clipboard with user alert on button click
- Visual indication for expired coupons (greyed out and struck-through)
- Simple shortcode `[saca_coupons]` to display coupons anywhere
- Lightweight, self-contained single-file PHP

## Installation

1. Upload the single PHP file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the "Coupon Aggregator" menu page.
4. Paste your coupons data in JSON format in the provided textarea.
5. Save changes.

## Setup

- Coupon JSON format example:


[
  {
    "title": "10% Off Storewide",
    "code": "SAVE10",
    "url": "https://affiliate.example.com/product?ref=123",
    "expiry": "2025-12-31"
  },
  {
    "title": "Free Shipping",
    "code": "FREESHIP",
    "url": "https://affiliate.example.com/shipping?ref=123"
  }
]


- `title` (string): The coupon title or description.
- `code` (string): The coupon code to apply.
- `url` (string): Your affiliate link or deal page URL.
- `expiry` (string, optional): Expiration date in YYYY-MM-DD format. Coupons without expiry never expire.

## Usage

- Insert the shortcode `[saca_coupons]` on any post/page where you want the coupon list.
- Visitors see a clean table of active coupons.
- Clicking "Use Code" automatically copies the coupon code to clipboard and opens the affiliate link in a new tab.

## Monetization

- The plugin can be offered free with limited coupon count and basic features.
- Premium upgrades can include:
  - Automatic coupon scraping and updates from affiliate platforms
  - Analytics for coupon clicks and conversions
  - Multi-store coupon management
  - Integration with WooCommerce or popular affiliate networks

## Support

For support, feature requests, or to report bugs, please contact the plugin author.

---

*This plugin aims to provide affiliate marketers and niche bloggers a streamlined way to display and manage affiliate coupons with enhanced user experience to maximize conversions and revenue.*
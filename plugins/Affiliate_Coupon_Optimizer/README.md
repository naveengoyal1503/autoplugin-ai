# Affiliate Coupon Optimizer

## Description
Aggregate affiliate coupons from multiple sources and dynamically display the best deals on your WordPress site. Automatically removes expired coupons to keep your offers fresh and increase affiliate conversions.

## Features
- Manage JSON-based coupon list via an admin settings page
- Automatically filter out expired coupons
- Display coupons on posts/pages with a shortcode `[affiliate_coupons]`
- Coupons show title, code, expiration date, and affiliate link
- Refresh expired coupons with a single click in admin

## Installation
1. Upload the plugin file to the `/wp-content/plugins/` directory or install via plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Coupon Optimizer' admin menu.

## Setup
1. In the plugin settings page, enter your coupons in JSON format. Each coupon should have:

[
  {
    "title": "Brand XYZ 10% Off",
    "code": "XYZ10",
    "link": "https://affiliate.link/xyz",
    "expires": "2026-01-31"
  },
  {
    "title": "SuperStore 20% Discount",
    "code": "SUPER20",
    "link": "https://affiliate.link/superstore",
    "expires": "2026-02-15"
  }
]

2. Save changes.
3. Use the shortcode `[affiliate_coupons]` where you want the coupon list to appear.

## Usage
- Add the shortcode `[affiliate_coupons]` to any page, post, or widget where you want to show current affiliate coupons.
- To remove expired coupons, visit the plugin settings and click "Refresh Expired Coupons".

## Monetization
Offer the plugin for free with basic JSON coupon management, then introduce premium features such as:
- Coupon categories
- Geo-targeting to show location-based coupons
- Analytics dashboard to track clicks and conversions
- Automated coupon retrieval from affiliate networks

## Support
For any issues or feature requests, please open an issue in the plugin repository or contact support.
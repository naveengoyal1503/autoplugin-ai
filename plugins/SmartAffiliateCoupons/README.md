# SmartAffiliateCoupons

SmartAffiliateCoupons is a WordPress plugin that allows affiliate marketers and bloggers to aggregate, manage, and display affiliate coupons easily on their sites. It optimizes affiliate marketing campaigns by providing user-friendly coupon displays with copy-to-clipboard and trackable affiliate links.

## Features

- Flexible JSON-based coupon management in admin
- Displays coupons with clear titles, codes, and affiliate URLs
- Copy-to-clipboard button for easy coupon code use
- Responsive, clean coupon display element for site visitors
- Simple shortcode `[smart_coupons]` to embed coupons anywhere
- Optimized for affiliate marketing and increasing conversions

## Installation

1. Upload the `smartaffiliatecoupons.php` file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress Plugins menu.
3. Go to the Smart Coupons menu in the admin dashboard.
4. Add your coupons as JSON in the settings textarea. Example:


[
  {
    "title": "20% Off Shoes",
    "code": "SHOES20",
    "url": "https://example-affiliate.com/product?code=SHOES20"
  },
  {
    "title": "Free Shipping Over $50",
    "code": "FREESHIP50",
    "url": "https://example-affiliate.com/shipping?code=FREESHIP50"
  }
]


5. Save changes.

## Setup

- Use the shortcode `[smart_coupons]` in any post, page, or widget where you want your coupons to appear.

## Usage

- Visitors can view and copy coupon codes easily.
- Clicking "Use Coupon" takes visitors to the affiliate URL to complete their purchase.

This plugin provides an elegant, conversion-focused way to showcase affiliate coupons and maximize revenue.

---

**Note:** Future premium upgrades will provide features like coupon auto-import from multiple affiliate networks, coupon expiration timers, and enhanced analytics.
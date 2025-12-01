# Affiliate Coupon Booster

## Features
- Easily add and manage affiliate coupons via JSON in the settings page.
- Display coupons anywhere using `[acb_coupons]` shortcode.
- Each coupon shows a code, description, and a clickable affiliate link.
- Minimal lean design to fit any theme.
- Basic click tracking via browser console (extendable to analytics).

## Installation
1. Upload the plugin single PHP file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Coupon Booster' menu in the WordPress admin sidebar.

## Setup
1. In the plugin settings page, input your coupons as a JSON array. Example:

[
  {
    "code": "SAVE20",
    "description": "20% off your purchase",
    "url": "https://example.com/product?affid=123"
  },
  {
    "code": "FREESHIP",
    "description": "Free shipping on orders over $50",
    "url": "https://example.com/checkout?affid=123"
  }
]

2. Save the settings.

## Usage
- Insert the shortcode `[acb_coupons]` in any post, page, or widget area to display the coupon list.
- Clicking 'Get Deal' redirects users with affiliate tracking.
- Admins can update coupons anytime in the settings.

## Monetization
- Offer a freemium version (this core version).
- Paid upgrades could include analytics dashboard, automated coupon updates, customizable coupon templates, or priority support.

## License
GPLv2 or later.
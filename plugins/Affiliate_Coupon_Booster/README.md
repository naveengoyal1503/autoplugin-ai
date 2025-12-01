# Affiliate Coupon Booster

Affiliate Coupon Booster is a WordPress plugin designed for affiliate marketers and niche bloggers to easily display and manage affiliate coupons and deals on their sites, helping increase conversions and revenue.

## Features

- Easily add and manage affiliate coupons via JSON input in the admin settings.
- Display coupon lists anywhere on your site using the `[affiliate_coupons]` shortcode.
- Automatically hides expired coupons based on expiry dates.
- Simple, lightweight, and responsive coupon display styles.
- Links open in new tabs with SEO-friendly attributes.
- Freemium-ready structure for future premium add-ons like coupon scheduling, tracking, and analytics.

## Installation

1. Upload the `affiliate-coupon-booster.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Affiliate Coupon Booster** to configure your coupons.

## Setup

- In the plugin settings page, enter your coupons in JSON format, for example:


[
  {
    "title": "50% off Widget Pro",
    "url": "https://affiliate.example.com/widgetpro?ref=yourID",
    "code": "WIDGET50",
    "expiry": "2025-12-31"
  },
  {
    "title": "25% Discount on Hosting",
    "url": "https://affiliate.example.com/hosting?ref=yourID",
    "code": "HOST25"
  }
]


- Save changes.

## Usage

- Place the shortcode `[affiliate_coupons]` inside any post, page, or widget where you want the coupon list to appear.
- Visitors will see active coupons with clickable affiliate links and easily copyable coupon codes.

## Future Enhancements

The plugin is designed to be extended with premium features like:
- Automated coupon import from affiliate networks.
- Performance tracking and analytics.
- Coupon scheduling and display rules.
- Integration with affiliate link managers.

## Support

For support, please submit your questions through the plugin support forum or contact the developer directly.

---

*Affiliate Coupon Booster helps you boost affiliate earnings by presenting compelling, up-to-date deals to your audience with minimal effort.*
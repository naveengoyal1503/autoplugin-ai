# Smart Affiliate Coupon Hub

## Description
Smart Affiliate Coupon Hub is a WordPress plugin that helps bloggers, marketers, and WooCommerce store owners automatically aggregate and display affiliate coupons from multiple networks. It includes features like real-time coupon display, easy shortcode integration, coupon code copy to clipboard, click tracking for conversion analytics, and an extendable freemium model.

## Features
- Aggregate affiliate coupons via JSON input
- Display coupons with clickable coupon codes and direct affiliate links
- Click tracking for affiliate link performance monitoring
- Copy coupon codes to clipboard with one click
- Shortcode `[sac_hub_coupons]` for easy insertion anywhere
- Admin interface to manage coupon data in JSON
- Lightweight, self-contained single PHP file plugin

## Installation
1. Upload the `smart-affiliate-coupon-hub.php` file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the 'Affiliate Coupons' menu in the WordPress admin dashboard.
4. Enter your coupons in JSON format (see the example below).

### Coupon JSON example

[
  {
    "title": "10% off Store X",
    "code": "SAVE10",
    "url": "https://affiliatelink.com/?ref=xyz"
  },
  {
    "title": "Free Shipping at Store Y",
    "code": "FREESHIP",
    "url": "https://affiliatelink.com/?ref=abc"
  }
]


## Usage
- Insert the shortcode `[sac_hub_coupons]` into any post, page, or widget to display current coupons.
- Visitors can click coupon codes to copy them.
- Clicking “Use Coupon” links will open affiliate sites in new tabs and track clicks.

## Support & Premium
This initial release provides basic coupon management and tracking.
Future versions will offer premium features such as:
- Automated affiliate coupon feed integration
- Advanced analytics and reporting
- Geo-targeted coupon display
- Link cloaking and expiration handling
- Priority support

Please report bugs or feature requests on the plugin support forum.

---

*Smart Affiliate Coupon Hub – leveraging the power of coupons to maximize your affiliate income with minimal effort.*
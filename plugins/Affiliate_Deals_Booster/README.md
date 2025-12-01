# Affiliate Deals Booster

## Description
Affiliate Deals Booster is a lightweight WordPress plugin designed to help affiliate marketers and bloggers aggregate and display their best coupon codes, deals, and discounts with ease. By dynamically showing timely offers, it helps increase affiliate conversions and boost revenue.

---

## Features

- Easy JSON-based deal management via WordPress Admin
- Shortcode `[affiliate_deals]` to display current deals anywhere
- Automatic filtering of expired deals
- Clean, simple front-end output styled with basic CSS
- No external dependencies or APIs required
- Supports adding discount text and expiration dates

---

## Installation

1. Upload the `affiliate-deals-booster.php` file to your WordPress plugins directory or install via custom plugin uploader.
2. Activate the plugin through the Plugins menu in WordPress.

---

## Setup

1. In the WordPress admin sidebar, click **Affiliate Deals Booster** to open the plugin settings.
2. Enter your deals in JSON format in the provided textarea. Each deal should be an object with these keys:


[
  {
    "title": "Product A Discount",
    "url": "https://affiliate-link.com/product-a",
    "discount": "20% OFF",
    "expiry": "2026-01-31"
  },
  {
    "title": "Service B Promo",
    "url": "https://affiliate-link.com/service-b"
  }
]


3. Save changes.

---

## Usage

- Use the shortcode `[affiliate_deals]` in posts, pages, or widgets to display your current affiliate deals list.
- Deals with expiry dates in the past will be automatically hidden.
- Style can be customized further by adding CSS to your theme.

---

## Changelog

### 1.0
- Initial release with JSON deal input and shortcode support.

---

## Support

If you run into issues or have suggestions, please contact the plugin author.
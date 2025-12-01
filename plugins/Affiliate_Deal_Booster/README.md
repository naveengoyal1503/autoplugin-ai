# Affiliate Deal Booster

**Version:** 1.0

## Description
Affiliate Deal Booster is a WordPress plugin designed to help affiliate marketers and bloggers effortlessly aggregate, display, and track coupon deals from affiliate programs on their sites. It boosts affiliate commissions by promoting validated, expiring discounts in an attractive, easy-to-use interface.

## Features
- Easy JSON-based coupon input in WordPress admin.
- Frontend shortcode `[aff_deals]` to display active coupons with discounts and expiration.
- Automatic expiration check to filter out old deals.
- Click tracking with anonymous count analytics.
- Responsive, minimal styling for seamless theme integration.
- Lightweight JavaScript to track clicks without impacting page load.

## Installation
1. Upload the plugin PHP file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Affiliate Deals' admin menu.
4. Enter your affiliate coupons in JSON format (see example).
5. Save changes.

## Setup
- The JSON format should be an array of objects, each object representing a coupon with the following keys:
  - `id`: Unique string identifier for the coupon.
  - `title`: Display title for the coupon.
  - `url`: Affiliate tracking URL.
  - `discount`: Visible discount off (e.g., "20%", "$10 off").
  - `expiry`: Expiration date in YYYY-MM-DD (optional).

Example JSON:

[
  {"id": "deal1", "title": "10% off Shoes", "url": "https://affiliate.com/track/deal1", "discount": "10%", "expiry": "2026-12-31"}
]


## Usage
- Insert the shortcode `[aff_deals]` into any page or post where you want the coupons displayed.
- Coupons past their expiry date will be hidden automatically.
- Track affiliate link clicks through plugin's click database (for later enhancement of analytics UI).

## Changelog
### 1.0
- Initial release with coupon aggregation, display, and click tracking.

## Support
For support and feature requests, please contact the author.
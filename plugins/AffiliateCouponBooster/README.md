# AffiliateCouponBooster

AffiliateCouponBooster is a WordPress plugin designed to help bloggers, affiliate marketers, and e-commerce websites easily create and display affiliate coupons and deals with built-in click tracking.

## Features

- Custom post type for managing coupons with affiliate URLs
- Add coupon codes, expiry dates, and descriptions for each coupon
- Track clicks on affiliate links automatically
- Display coupons anywhere using a simple `[affiliate_coupons]` shortcode
- Automatically hides expired coupons
- Custom admin columns for quick coupon overview
- User-friendly interface for managing coupons

## Installation

1. Upload the `affiliatecouponbooster.php` file to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add new coupons via the "Affiliate Coupons" menu in your WordPress admin

## Setup

- When adding a new coupon, enter the affiliate destination URL, optional coupon code, and expiry date.
- Use the shortcode `[affiliate_coupons count="5"]` to display the latest 5 coupons on any page or post.
- Customize styles via your theme or add CSS to target `.acb-coupon-list` and `.acb-coupon-item` classes.

## Usage

- Insert the shortcode `[affiliate_coupons]` in posts, pages, or widgets to display active coupons.
- Visitors click "Get Deal" buttons and are redirected through the plugin, increasing click tracking accuracy.

## Changelog

### 1.0
- Initial release with coupon custom post type, click tracking, shortcode display, and expiry filtering.
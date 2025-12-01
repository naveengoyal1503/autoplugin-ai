# Smart Affiliate Coupons

**Version:** 1.0

## Description
Smart Affiliate Coupons is a WordPress plugin that automatically aggregates affiliate coupons and deals from your chosen feed, personalizes links with your affiliate ID, and displays them beautifully on your site to boost conversions and affiliate revenue.

## Features

- Connect to any JSON coupon feed URL.
- Automatically adds your affiliate ID to coupon links.
- Scheduled hourly update of coupons.
- Displays coupons via simple shortcode `[smart_aff_coupons]`.
- Responsive, clean coupon display with code and expiry info.
- Admin settings page for easy setup.

## Installation

1. Upload the `smart-affiliate-coupons.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Go to Settings > Smart Affiliate Coupons.
4. Enter your Affiliate ID and coupon feed URL (a JSON feed with coupons).
5. Save changes.

## Setup

- Your coupon feed should be a publicly accessible JSON endpoint returning an array of coupons with keys like `title`, `code`, `link`, `description`, and `expiry`.
- The plugin automatically fetches and caches coupons hourly.

## Usage

- Insert the shortcode `[smart_aff_coupons]` into any post, page, or widget to display the coupon list.
- Visitors will see fresh, personalized affiliate coupons ready to use.

## Monetization

The plugin is free to use with core features. Premium upgrades can include advanced coupon personalization, analytics dashboard, multiple affiliate IDs, and email alerts available via subscription.

---

Thank you for using Smart Affiliate Coupons!
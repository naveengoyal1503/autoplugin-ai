# Affiliate Coupon Booster

## Description
Affiliate Coupon Booster allows affiliate marketers to create and display dynamic coupon codes linked to their affiliate URLs to increase conversion rates and track performance.

## Features

- Add and manage multiple affiliate coupons through a JSON input in WordPress settings.
- Display coupons anywhere on your site with the shortcode `[affiliate_coupons]`.
- Each coupon includes a unique code, description, and affiliate link.
- Simple and lightweight single PHP file plugin.

## Installation

1. Upload the `affiliate-coupon-booster.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > Affiliate Coupon Booster to configure your coupons.

## Setup

- Enter coupon details in JSON format. Example:


[
  {"code":"SAVE20", "affiliate_url":"https://affiliatesite.com/?ref=123", "desc":"Save 20% on all items"},
  {"code":"FREESHIP", "affiliate_url":"https://affiliatesite.com/?ref=456", "desc":"Free Shipping on orders $50+"}
]


- Save settings.

## Usage

- Place the shortcode `[affiliate_coupons]` in pages, posts, or widgets where you want the coupons to appear.

- Users clicking "Use Coupon" will be redirected to the affiliate link with the coupon code shown.

## Monetization

- The free version provides basic coupon management.
- The premium version (to be released) will offer advanced affiliate link tracking, analytics dashboard, coupon expiration, and automated updates for affiliate deals.

---
*Designed to help affiliate marketers convert more visitors and boost revenue by simplifying coupon promotion.*
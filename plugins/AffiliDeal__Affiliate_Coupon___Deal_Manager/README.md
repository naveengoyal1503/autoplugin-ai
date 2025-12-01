# AffiliDeal: Affiliate Coupon & Deal Manager

## Description

AffiliDeal allows WordPress site owners to effortlessly create, manage, and display affiliate coupons and deals to increase conversions and monetize affiliate partnerships effectively.

## Features

- Custom post type for coupons with affiliate URLs, discount codes, and expiration dates.
- Easy-to-use admin interface for adding and editing coupons.
- Shortcode `[affilideal_coupons]` to display coupons anywhere.
- Optionally hide expired coupons.
- Simple, clean, and responsive coupon display.
- Fully self-contained single PHP file plugin for easy installation.

## Installation

1. Upload the `affilideal.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add new coupons under the "AffiliDeal Coupons" menu.

## Setup

1. Go to **AffiliDeal Coupons** &rarr; **Add New**.
2. Enter a coupon title, description, affiliate URL, optional discount code, and expiration date.
3. Publish the coupon.

## Usage

- Use the shortcode `[affilideal_coupons count="5" show_expired="no"]` to display coupons on any page or post.
  - `count` controls how many coupons to show (default 5).
  - `show_expired` can be `yes` or `no` to show/hide expired coupons (default `no`).

Example:

[affilideal_coupons count="10" show_expired="yes"]

This will display the 10 most recent coupons including expired ones.

## Monetization

Use the plugin to grow affiliate revenue by showcasing coupons linked directly to affiliate programs. The freemium model can be enhanced later with premium features such as automated affiliate link cloaking, expiration notifications, and affiliate network API integration.
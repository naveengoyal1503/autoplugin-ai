# Affiliate Coupon Hub

Affiliate Coupon Hub is a WordPress plugin that helps affiliate marketers, bloggers, and online store owners create and display exclusive coupons and deals linked to affiliate programs. Boost your affiliate conversions by offering your visitors easy access to discount codes.

## Features

- Easy management of coupons via JSON input in WordPress admin
- Display coupons anywhere using shortcode `[affiliate_coupons]`
- Click-to-copy coupon codes for user convenience
- Affiliate links attached to coupons to track conversions
- Simple and lightweight with no external dependencies

## Installation

1. Upload `affiliate-coupon-hub.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Setup

1. Navigate to the "Coupon Hub" menu in the WordPress admin sidebar.
2. Add your coupons in JSON format, for example:


[
  {
    "title": "10% off Shoes",
    "code": "SHOES10",
    "description": "Get 10% discount on all shoes.",
    "affiliate_url": "https://affiliate.example.com/?product=shoes&ref=123"
  }
]

3. Save your changes.

## Usage

- Use the shortcode `[affiliate_coupons]` in posts, pages, or widgets to display the coupon list.
- Visitors can click the coupon code to copy it to their clipboard.
- The "Shop Now" button links visitors through your affiliate URL to earn commissions.

---

Boost your affiliate marketing earnings while providing value to your audience with Affiliate Coupon Hub!
# Affiliate Coupon Booster

Affiliate Coupon Booster is a simple, effective WordPress plugin that automates displaying attractive coupon offers linked with your affiliate marketing URLs to boost conversions and site revenue.

## Features

- Easy management of coupons via a JSON input in the admin panel
- Shortcode `[affiliate_coupons]` to display all coupons in a neat, responsive layout
- Click-to-copy coupon codes for user convenience
- Affiliate link integration to drive sales
- Lightweight, self-contained, no external dependencies

## Installation

1. Upload the `affiliate-coupon-booster.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the **Affiliate Coupons** menu in admin to add your coupons as a JSON array.

## Setup

- Enter coupons data as JSON in the format:


[
  {
    "title": "10% Off",
    "code": "SAVE10",
    "link": "https://affiliate.example.com/product?ref=123"
  },
  {
    "title": "Free Shipping",
    "code": "FREESHIP",
    "link": "https://affiliate.example.com/shipping?ref=123"
  }
]


- Save changes.

## Usage

- Use the shortcode `[affiliate_coupons]` in posts, pages, or widgets to show the coupons.
- Visitors can click coupon codes to copy easily and click the button to navigate through your affiliate links.

## Monetization

This plugin facilitates affiliate marketing, a strategy that can generate passive income by promoting products through unique affiliate links with coupons to enhance conversion rates and sales volume.
# Affiliate Coupon Aggregator

Affiliate Coupon Aggregator dynamically displays curated affiliate coupons and deals on your WordPress site, helping you boost user engagement and affiliate revenue.

## Features

- Display affiliate coupons via shortcode `[affiliate_coupons]`
- Easy JSON-based coupon management in admin settings
- Automatically appends affiliate IDs to coupon URLs
- Copy-to-clipboard button for coupon codes
- Clean, responsive coupon list display

## Installation

1. Upload the plugin PHP file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Navigate to **Settings > Affiliate Coupons** to configure coupons and your affiliate ID.

## Setup

- Enter your coupons in JSON format, for example:


[
  {"title":"20% off all products","code":"SAVE20","url":"https://shop.example.com/product?ref=affiliate"},
  {"title":"Free Shipping","code":"FREESHIP","url":"https://shop.example.com/shipping?ref=affiliate"}
]


- Add your affiliate ID to automatically append to all coupon URLs.

## Usage

- Place the shortcode `[affiliate_coupons]` on any page or post to display the coupons list.
- Visitors can copy coupon codes to clipboard with a single click.

This plugin offers a simple, effective way for bloggers and affiliate marketers to monetize by promoting affiliate deals with a professional look and ease of use.
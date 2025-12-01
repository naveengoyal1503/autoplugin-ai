# Affiliate Coupon & Deal Booster

A WordPress plugin that dynamically creates a coupon and deal section to boost your affiliate marketing revenue by displaying attractive coupon codes and affiliate links.

## Features

- Simple admin interface to add coupons/deals using JSON
- Shortcode `[acdb_coupons]` to display coupon sections anywhere
- Widget support to showcase deals in sidebars
- Responsive and clean design for coupons
- External affiliate link integration with nofollow

## Installation

1. Upload the plugin PHP file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Affiliate Coupon Booster** to add your coupons in JSON format.

## Setup

- Add coupons in this JSON format (copy this example for your editing):


[
  {
    "title": "10% off Shoes",
    "code": "SHOES10",
    "url": "https://youraffiliate.link/product"
  },
  {
    "title": "Free Shipping on Orders $50+",
    "code": "FREESHIP",
    "url": "https://youraffiliate.link/shipping"
  }
]


- Save changes.

## Usage

- Use the shortcode `[acdb_coupons]` in posts, pages, or widgets to display the coupon list.
- Add the **Affiliate Coupons Widget** to your sidebar or footer to show deals dynamically.

## Monetization Model

This plugin can be distributed as freemium:

- Free version: Basic coupon input, shortcode, and widget display
- Premium version (future): Advanced analytics, multiple affiliate networks integration, priority support

## Support

For bug reports and feature requests, please reach out to the plugin author via the WordPress plugin repository support or your website contact form.
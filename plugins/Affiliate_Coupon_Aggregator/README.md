# Affiliate Coupon Aggregator

## Description
Automatically aggregate and display affiliate coupons from multiple ecommerce stores using a simple shortcode. Perfect for affiliate marketers and coupon bloggers to increase conversions and commissions.

## Features

- Store and display multiple coupons with title, code, URL, and description
- Easy shortcode `[affiliate_coupons]` for front-end display
- Admin interface to manage coupon data via JSON input
- Clean, minimal styling for coupon listings

## Installation

1. Upload the plugin PHP file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

## Setup

1. Navigate to **Coupon Aggregator** menu in the WordPress admin
2. Enter your coupon data as JSON array with objects containing:
   - `title` (string, required)
   - `code` (string, required)
   - `url` (string, required)
   - `description` (string, optional)
3. Save the coupons

Example JSON:


[
  {
    "title": "20% Off Shoes",
    "code": "SHOE20",
    "url": "https://example.com/shoes",
    "description": "Save 20% on all shoes storewide"
  },
  {
    "title": "Free Shipping",
    "code": "FREESHIP",
    "url": "https://example.com/all",
    "description": "Free shipping on orders over $50"
  }
]


## Usage

- Insert shortcode `[affiliate_coupons]` into any page, post, or widget to display the coupon list.
- Coupons will show title, code, and a button linking to the affiliate product page.

---
# Affiliate Coupon & Deal Booster

## Description
Affiliate Coupon & Deal Booster helps affiliate marketers and bloggers increase conversions by managing exclusive coupon codes and deals easily within WordPress. Display active coupons on your site with a simple shortcode.

## Features
- Create and manage unlimited affiliate coupons using a clean JSON interface
- Display coupons anywhere using `[affiliate_coupons]` shortcode
- Automatically hides expired coupons
- Mobile-responsive coupon layout
- Simple admin page for adding/editing coupons

## Installation
1. Upload the plugin PHP file to your `/wp-content/plugins/` directory or install via WordPress admin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Affiliate Coupons' menu in WordPress admin to add your affiliate coupons in JSON format.

## Setup
Prepare your coupons as a JSON array with fields: `title`, `code`, `url`, `description`, and `expiry` (format YYYY-MM-DD). Example:


[
  {
    "title": "20% OFF",
    "code": "SAVE20",
    "url": "https://affiliate.example.com/product?code=SAVE20",
    "description": "Save 20% on selected products",
    "expiry": "2026-12-31"
  }
]


Paste this JSON in the plugin settings page under 'Affiliate Coupons'. Save changes.

## Usage
Add the shortcode `[affiliate_coupons]` in any post or page to display the active affiliate coupons with their codes and corresponding links.

## Monetization
Offer the plugin free for basic coupon management, then develop premium add-ons for automated coupon imports from affiliate networks, detailed analytics tracking, and white-label branding to generate revenue.
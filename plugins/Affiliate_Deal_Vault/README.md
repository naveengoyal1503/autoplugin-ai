# Affiliate Deal Vault

Affiliate Deal Vault is a WordPress plugin to create and display curated affiliate coupons and deals easily through shortcode integration. Enhance your affiliate marketing revenue by showcasing timely offers with minimal setup.

## Features

- Add and manage affiliate deals via a JSON input in admin
- Frontend shortcode `[adv_deal_vault]` to display active deals
- Automatically hide expired deals based on expiry date
- Lightweight and self-contained single PHP file

## Installation

1. Upload the plugin file to your WordPress `wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open the 'Deal Vault' menu in WordPress Admin.
4. Enter your affiliate deals as a JSON array in the provided textarea.
5. Click 'Save Changes'.

## Setup

Enter deals in JSON format like this:


[
  {
    "title": "10% Off Store X",
    "url": "https://affiliatelink.com/storex",
    "expiry": "2025-12-31"
  },
  {
    "title": "Free Shipping at Shop Y",
    "url": "https://affiliatelink.com/shopy"
  }
]


Fields:

- `title` (string, required): Deal description
- `url` (string, required): Affiliate link
- `expiry` (string, optional): YYYY-MM-DD format; deals past this date are hidden

## Usage

1. Place shortcode `[adv_deal_vault]` in any post, page, or widget where you want deals displayed.
2. Users see a list of current deals with clickable affiliate links.

This plugin provides a simple but effective tool to run affiliate coupon/deal marketing as a revenue stream on your WordPress site.
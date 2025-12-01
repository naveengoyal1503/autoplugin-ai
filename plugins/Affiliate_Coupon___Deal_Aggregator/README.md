# Affiliate Coupon & Deal Aggregator

## Description

This plugin aggregates coupon and deal RSS feeds from multiple providers, automatically integrates your affiliate tracking parameters into coupon links, and displays them in a clean, mobile-friendly list. It enables bloggers, affiliate marketers, and ecommerce websites to monetize through affiliate commissions by offering their visitors a curated collection of active coupons and deals.

## Features

- Import and aggregate multiple coupon/deal RSS feeds
- Automatically append affiliate tracking parameters to links
- Limit displayed coupons for performance
- Shortcode support to easily embed coupons anywhere
- Admin settings page for managing feeds and affiliate ID
- Clean and responsive coupon display

## Installation

1. Upload the plugin file to the `/wp-content/plugins/` directory or install via WordPress plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the "Affiliate Coupons" menu in the WordPress admin to configure coupon feed URLs and your affiliate ID.
4. Save settings.

## Setup

- Enter each coupon or deal RSS feed URL you want to aggregate, one per line.
- Specify your affiliate tracking parameter (e.g., `affid=1234`) to automatically append to all coupon URLs.
- Save the settings.

## Usage

- Use the shortcode `[acda_coupons]` in any page or post to display the aggregated coupon list.
- The plugin will fetch the latest coupons from configured feeds and show them with your affiliate links.

## Monetization Notes

- The plugin uses a freemium concept where the basic feed aggregation and affiliate linking are free.
- You can extend the plugin later with premium features like automatic affiliate link cloaking, detailed analytics, or sponsored coupon slots.

## Support

For any issues or feature requests, please contact the plugin author or contribute on the plugin's repository.
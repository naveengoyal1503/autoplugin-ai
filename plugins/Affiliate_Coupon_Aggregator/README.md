# Affiliate Coupon Aggregator

A WordPress plugin that aggregates discount coupons from multiple affiliate networks, allowing site owners to display categorized and updated affiliate coupons to increase revenue.

## Features

- Automatically fetches and updates coupons every 12 hours from configured affiliate APIs (simulated in demo).
- Supports coupon categorization for targeted display.
- Easy shortcode usage to embed coupon lists on any post or page.
- Clean and simple coupon display design, customizable via CSS.
- Admin panel for adding API keys and managing coupon categories.

## Installation

1. Upload the plugin ZIP file to your WordPress admin via Plugins > Add New > Upload.
2. Activate the plugin.
3. Go to Settings > Affiliate Coupons to enter your affiliate network API key and set coupon categories.

## Setup

- Enter your affiliate API key provided by your coupon affiliate network.
- Specify relevant coupon categories as comma-separated values (e.g., Electronics, Apparel, Shipping).
- Save settings.

## Usage

- Use the shortcode `[affiliate_coupons]` to display all coupons.
- Use the shortcode with category filter: `[affiliate_coupons category="Electronics"]` to display only coupons in the Electronics category.

Example:

html
[affiliate_coupons]

[affiliate_coupons category="Apparel"]


## Monetization

The plugin is designed with a freemium model in mind:

- Free version includes basic coupon aggregation and shortcode display.
- Premium upgrades can unlock advanced filter options, multiple affiliate network integrations, customizable templates, and priority timetable updates.

This plugin provides consistent affiliate revenue growth opportunities by delivering fresh and categorized coupons content easily integrated on WordPress sites.
# Affiliate Booster Pro

Affiliate Booster Pro is a powerful WordPress plugin designed for affiliate marketers and bloggers who want to create, manage, and display affiliate coupon deals and special discounts with ease to boost affiliate revenue.

## Features

- Custom post type for managing affiliate coupons
- Add coupon codes, descriptions, and affiliate URLs
- Frontend shortcode `[abp_coupons]` to display coupons attractively
- Tracks user clicks on affiliate coupon links
- Lightweight JavaScript handles click tracking and redirection
- Responsive and minimal styling

## Installation

1. Upload the plugin PHP file to your `/wp-content/plugins/` directory or install via WordPress admin interface if packaged as a zip.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You will see a new 'Coupons' menu in the WordPress admin sidebar.

## Setup

1. Go to **Coupons > Add New** to create your first coupon.
2. Enter the coupon title (deal title), description, coupon code, and set the affiliate URL as a custom field named `_abp_affiliate_link`.
3. Publish the coupon.

## Usage

- Insert the shortcode `[abp_coupons count="5"]` in any post or page to show the latest 5 coupons.
- Clicking the coupon button tracks the click and opens the affiliate link in a new tab.

---

This plugin can be extended with premium features such as advanced analytics, automatic fetching of deals from affiliate networks, and multi-network integration to increase monetization potential.
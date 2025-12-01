# AffiliateCoupon Connector

AffiliateCoupon Connector automatically aggregates and displays up-to-date affiliate coupons and deals from multiple affiliate program APIs on your WordPress site, helping you monetize through affiliate marketing by offering exclusive discounts your visitors want.

## Features

- Import coupons from multiple external affiliate program JSON APIs
- Automatic daily synchronization of coupon data
- Avoid duplicate coupons with filtering
- Display coupons anywhere using a simple shortcode: `[affiliate_coupons]`
- Customize number of displayed coupons via shortcode or admin settings
- Easy-to-use settings page to add affiliate API endpoints
- Designed to increase user engagement and boost affiliate commissions

## Installation

1. Upload the `affiliatecoupon-connector.php` file to the `/wp-content/plugins/` directory or install via WordPress plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > AffiliateCoupon Connector** in the WordPress admin to add affiliate coupon JSON API URLs.
4. Save changes to start automatic coupon synchronization.
5. Insert the shortcode `[affiliate_coupons]` into posts, pages, or widgets to display the coupons.

## Setup

- In the settings page, enter one affiliate coupon JSON API URL per line.
- Optionally set how many coupons to display (default is 5).
- The plugin automatically fetches and caches coupons daily.

## Usage

- Place the shortcode `[affiliate_coupons]` in any post or page to show the latest coupons.
- You can also specify number of coupons: `[affiliate_coupons count="10"]`
- Style the coupon display via your theme CSS if desired.

Enjoy boosting your site's revenue with fresh, relevant affiliate coupons delivered automatically!
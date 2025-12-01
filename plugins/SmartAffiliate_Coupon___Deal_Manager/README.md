# SmartAffiliate Coupon & Deal Manager

## Description
SmartAffiliate is a WordPress plugin designed for affiliate marketers and bloggers who want to increase affiliate revenue by creating and managing exclusive coupons and deals. It supports dynamic expiration dates, geo-targeting by country, and tracks clicks on affiliate links to optimize conversions.

## Features

- Custom Coupon post type to create/manage coupons
- Affiliate URL input with dynamic redirection
- Expiry date support to automatically disable expired coupons
- Geo-targeting: restrict coupon usage by visitor's country
- Click tracking for coupon usage analytics
- Shortcode `[smartaffiliate_coupon id="COUPON_ID" text="Button Text"]` to display coupon call-to-action buttons
- Simple, lightweight, and self-contained single PHP file

## Installation

1. Upload `smartaffiliate-coupon-manager.php` to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress admin.
3. Create coupons via the new 'Coupons' menu in the WordPress admin sidebar.

## Setup

1. Go to 'Coupons' > 'Add New'.
2. Enter the Coupon title and description.
3. Fill in the 'Coupon Details' meta box:
   - Affiliate URL (required): Your affiliate link for the deal.
   - Expiry Date (optional): Select a UTC datetime to disable coupon after expiry.
   - Geo Targeting (optional): Comma-separated list of country codes (e.g. US,CA,GB).
4. Publish the coupon.

## Usage

- Insert the shortcode `[smartaffiliate_coupon id="123" text="Get 20% Off Now!"]` in posts, pages, or widgets to display a coupon button.
- Visitors clicking the button are redirected through your affiliate link with tracking.
- Expired or geo-restricted coupons show informative messages instead of the button.

## Monetization Strategy

The plugin supports a freemium model. This basic version is free and enables essential coupon management features. Premium plans (to be developed) can add advanced analytics, multiple affiliate link variants, A/B testing, and integration with affiliate networks.

## Support

Post feature requests or issues to the plugin support forum on WordPress.org.

---

**Increase affiliate conversions and revenue by managing smart coupons easily!**
# Affiliate Coupon Booster

A WordPress plugin to create a dedicated coupon and deals section with affiliate link cloaking and tracking to boost affiliate marketing commissions.

## Features

- Custom post type for coupons with affiliate links.
- Coupon code display and description.
- Cloaked affiliate URLs for clean, trustworthy links.
- Automatically redirects cloaked URLs to the affiliate destination.
- Admin UI for easy coupon management.
- Shortcode `[acb_coupons]` to display a styled list of coupons anywhere.
- Settings to customize cloaking prefix slug.

## Installation

1. Upload the `affiliate-coupon-booster.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to "Coupon Booster" menu in WordPress admin.

## Setup

- Add new coupons under "Coupons" submenu. Provide a coupon title, description, affiliate link URL, and optionally a coupon code.
- Go to "Settings" submenu under Coupon Booster to customize the affiliate link prefix used for cloaking URLs (default is "deal").

## Usage

- Use the shortcode `[acb_coupons]` in pages, posts, or widgets to display the list of active coupons.
- The plugin will automatically cloak affiliate URLs in the format: `yoursite.com/{prefix}/{coupon_id}` for cleaner links.
- Visitors clicking the cloaked links will be redirected to the original affiliate URL.

## Example

Add shortcode:


[acb_coupons]


Output:

- Coupon title linked to cloaked URL.
- Coupon code displayed if set.
- Short description.
- A 'Use Coupon' button linking via cloaked affiliate link.

## Monetization

Free core plugin with potential premium upgrades including:

- Advanced affiliate network integrations
- Expiry and scheduling of coupons
- Detailed affiliate click analytics
- Multi-user coupon management

---

*Build your affiliate revenue by offering your audience a trustworthy, easy-to-use coupon hub with Affiliate Coupon Booster.*
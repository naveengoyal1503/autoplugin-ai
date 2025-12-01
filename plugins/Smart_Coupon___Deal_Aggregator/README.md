# Smart Coupon & Deal Aggregator

## Description
Aggregates and manages coupon codes and deals from multiple affiliate programs. Monetize your WordPress site by offering users easy access to exclusive coupons, driving affiliate commissions and optionally offering premium featured deals.

## Features
- Add and manage unlimited coupon codes with title, description, expiry date, and affiliate URL.
- Highlight featured coupons for premium advertising or sponsorship.
- Frontend shortcode `[scda_display_coupons]` to display all or featured coupons with a copy-to-clipboard button for user convenience.
- Automatically hides expired coupons.
- Simple admin interface in WordPress dashboard.
- Monetization via affiliate marketing and potential premium coupon sponsorship.

## Installation
1. Download the plugin PHP file.
2. Upload the file to your WordPress site under `/wp-content/plugins/`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Access "Coupon Deals" menu in WordPress admin to add and manage coupons.

## Setup
1. Go to **Coupon Deals** in your WordPress admin sidebar.
2. Use the form to add new coupons or deals. Fields:
   - Title of the deal
   - Description
   - Coupon code
   - Affiliate URL (where clicks will be sent)
   - Optional expiry date
   - Checkbox if the coupon is featured
3. Save your changes.

## Usage
### Display coupons on any page/post
Add the shortcode:

[scda_display_coupons limit="5" featured_only="no"]

- `limit` controls how many coupons to show (default 10).
- `featured_only` set to "yes" shows only featured coupons.

Users will see clickable coupons with a copy button for easy use of coupon codes.

## Monetization Strategy
- Earn affiliate commissions on coupon-driven sales.
- Attract sponsors to feature their coupons as paid advertisements.
- Optionally, sell premium subscriptions granting access to exclusive or early-release deals.

## Support
For support, open an issue on the plugin’s repository or contact the developer.
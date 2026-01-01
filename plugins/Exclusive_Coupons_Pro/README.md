# Exclusive Coupons Pro

## Features

- **Generate Exclusive Coupons**: Create unique, trackable coupon codes with affiliate links.
- **Auto-Expiration**: Coupons expire automatically based on date or usage limits.
- **Click Tracking**: Monitor usage and conversions (Pro: Advanced analytics).
- **Shortcode Integration**: Easy `[exclusive_coupon id="0"]` embedding in posts/pages.
- **One-Click Redemption**: Beautiful, mobile-responsive coupon displays.
- **Freemium Model**: Free for basics, Pro ($49/year) for unlimited features.

**Pro Features**: Custom branding, email capture, A/B testing, WooCommerce integration.

## Installation

1. Upload the `exclusive-coupons-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupons Pro** to add your coupons in JSON format.

## Setup

1. In the admin panel: **Settings > Coupons Pro**.
2. Enter coupons as JSON array, e.g.:
   
   [
     {"code":"SAVE20","afflink":"https://youraffiliate.com/?ref=123","expiry":"2026-12-31","uses":"10"}
   ]
   
3. Save. Note the ID (0 for first coupon).
4. Use shortcode: `[exclusive_coupon id="0"]`.

## Usage

- Embed shortcodes in posts, pages, or widgets.
- Track clicks via admin (Pro: Detailed dashboard).
- Customize styles via CSS.

## Support

Report issues at [support@example.com](mailto:support@example.com). Upgrade to Pro for priority support.

## Changelog

**1.0.0** - Initial release.
# Custom Coupon Affiliate Pro

## Features

- **Easy Coupon Creation**: Custom post type for coupons with code, affiliate link, usage limits.
- **Shortcode Integration**: Use `[ccap_coupon id="123"]` to display trackable coupons anywhere.
- **Click & Usage Tracking**: Monitors uses and clicks with unique tracking IDs.
- **Frontend Display**: Beautiful, responsive coupon boxes with redemption buttons.
- **Admin Stats Dashboard**: View usage and click data.
- **Freemium Model**: Free for basics; Pro unlocks unlimited coupons, exports, custom branding ($49/yr).

**Pro Features (Upsell)**: Advanced analytics, email capture, auto-expiry, WooCommerce integration.

## Installation

1. Upload the `custom-coupon-affiliate-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Coupons > Add New** to create your first coupon.
4. Use shortcode `[ccap_coupon id="X"]` (replace X with coupon ID) in posts/pages.

## Setup

1. **Create a Coupon**:
   - Title: e.g., "50% Off Hosting"
   - Content: Description
   - Custom Fields (via meta box or code): `coupon_code` (e.g., "SAVE50"), `affiliate_link` (your aff URL), `max_uses` (e.g., 100)
2. **Embed**: Add shortcode to any page/post.
3. **Track**: View stats under Coupons > Stats.

## Usage

- **Shortcode Example**: `[ccap_coupon id="123"]` renders a coupon box.
- **Customization**: Style via CSS targeting `.ccap-coupon`.
- **Monetization**: Earn affiliate commissions on tracked clicks. Promote Pro for more features.
- **Support**: Free version forums; Pro email support.

**Upgrade**: [Get Pro](https://example.com/pro) for full power!

## Changelog

- 1.0.0: Initial release.
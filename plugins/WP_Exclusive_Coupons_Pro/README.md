# WP Exclusive Coupons Pro

## Features

- **Generate Exclusive Coupons**: Create custom coupon codes with descriptions, affiliate links, and expiration dates.
- **Click Tracking**: Track coupon redemptions via unique affiliate parameters.
- **Auto-Expiration**: Coupons automatically hide after expiry date.
- **One-Click Copy**: Users can copy coupon codes instantly.
- **Shortcode Integration**: Easy embed with `[wpec_coupon id="0"]`.
- **Freemium Model**: Free for basics; Pro unlocks unlimited coupons, analytics, custom branding ($49/year).

**Pro Only**: Advanced analytics, bulk import, email notifications, custom designs.

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-exclusive-coupons-pro`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Coupons Pro** in the admin menu to add your first coupon.

## Setup

1. Go to **Coupons Pro** dashboard.
2. Enter coupons as JSON array, e.g.:
   
   [
     {"code":"SAVE20","afflink":"https://youraffiliate.com/?ref=wpec","desc":"20% Off","expires":"2026-12-31"}
   ]
   
3. Save. Note the ID (starts at 0).
4. Embed in posts/pages: `[wpec_coupon id="0"]`.

## Usage

- **Frontend**: Displays styled coupon box with copy button and affiliate link.
- **Backend**: Edit coupons anytime; tracks clicks via unique params.
- **Monetization Tips**: Partner with brands for exclusive codes to boost commissions [1][2].
- **Shortcode Options**: `id` (required, coupon index).

## FAQ

**How do I track performance?** Pro version provides click analytics dashboard.

**Can I style it?** Yes, target `.wpec-coupon` classes or Pro custom CSS.

## Upgrade to Pro

Unlock full potential: [Upgrade Now](https://example.com/upgrade) for $49/year.

**Support**: Contact support@example.com
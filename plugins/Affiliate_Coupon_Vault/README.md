# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons via simple JSON in admin settings (Pro: truly unlimited).
- **Click Tracking**: Tracks coupon usage and displays stats to build trust and urgency.
- **Shortcode Display**: Use `[acv_coupons]` or `[acv_coupons limit="3"]` anywhere.
- **Randomized Deals**: Shuffles coupons for fresh visitor experience.
- **Affiliate Optimized**: Direct links with tracking to maximize commissions.
- **Pro Features** (Upgrade for $49): Auto-expiration, WooCommerce integration, analytics dashboard, personalized coupons, email capture.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to add coupons.

## Setup

1. In **Settings > Coupon Vault**, paste JSON like:
   
   [
     {"name":"SAVE10","code":"SAVE10","afflink":"https://yourafflink.com/?ref=10","desc":"10% off sitewide"},
     {"name":"FREESHIP","code":"FREESHIP","afflink":"https://yourafflink.com/ship","desc":"Free shipping"}
   ]
   
2. Save changes.
3. Add `[acv_coupons]` to any post/page.

## Usage

- **Frontend**: Coupons display with codes, descriptions, click stats, and trackable affiliate buttons.
- **Customization**: Style via CSS targeting `.acv-vault`, `.acv-coupon`, `.acv-btn`.
- **Tracking**: Clicks logged by IP to prevent duplicates; view totals on each coupon.
- **Monetization Tip**: Partner with brands for exclusive codes to boost conversions.[1][2]

## Pro Upgrade

Unlock advanced features: analytics, auto-coupon generation, integrations. Visit [example.com/pro](https://example.com/pro).

## Support

Report issues in WordPress forums or contact support@example.com.
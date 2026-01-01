# Affiliate Coupon Vault

## Features
- **Easy Coupon Management**: Add unlimited affiliate coupons via simple JSON in admin settings (Pro: truly unlimited).
- **Trackable Links**: Monitors clicks and displays usage stats to boost credibility.
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` to embed coupons anywhere.
- **Conversion-Optimized Design**: Eye-catching, mobile-responsive coupon displays.
- **Affiliate-Friendly**: Perfect for bloggers promoting deals from Amazon, software, etc.[1][2]
- **Pro Features**: Auto-coupon generation, analytics dashboard, custom branding, API for dynamic deals ($49/year).

## Installation
1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your first coupons.

## Setup
1. In admin, enter coupons as JSON array:
   
   [
     {"code":"SAVE10","afflink":"https://your-affiliate-link.com","desc":"10% off on tools"},
     {"code":"DEAL20","afflink":"https://another-link.com","desc":"20% off hosting"}
   ]
   
2. Save and note the ID (0,1,...).
3. Free version limited to 5 coupons.

## Usage
- Embed with shortcode: `[affiliate_coupon id="0"]` in posts/pages.
- Clicks auto-tracked; stats shown on button.
- Upgrade to Pro for advanced tracking and unlimited use: [Get Pro](https://example.com/pro).

## Support
Contact support@example.com. Freemium model ensures profitability for developers.[7]
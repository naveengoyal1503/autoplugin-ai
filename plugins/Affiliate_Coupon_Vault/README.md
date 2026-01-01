# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited affiliate coupons via simple JSON in admin settings (Pro: truly unlimited + auto-generation).
- **Click Tracking**: Tracks every coupon click for performance analytics (Pro: detailed reports and exports).
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` to display coupons anywhere.
- **Conversion Boost**: Exclusive deals position your site as a savings hub, increasing affiliate commissions.[1][2]
- **Mobile-Responsive**: Clean, professional design works on all devices.
- **Freemium Model**: Free for basics, Pro unlocks advanced features like integrations with WooCommerce, email capture, and A/B testing.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons.
4. Use the shortcode `[affiliate_coupon id="0"]` in posts/pages.

## Setup

1. In **Settings > Coupon Vault**, enter coupons in JSON format:
   
   [
     {"code": "SAVE20", "afflink": "https://youraffiliate.link", "desc": "20% Off Sitewide"}
   ]
   
2. Save. Each array index becomes the shortcode ID (e.g., id="0" for first coupon).
3. Embed shortcodes in content for instant coupon displays.

## Usage

- **Display Coupon**: `[affiliate_coupon id="1"]` shows the second coupon.
- **Track Performance**: Clicks auto-tracked (view in Pro dashboard).
- **Customization**: Style via CSS targeting `.acv-coupon`.
- **Pro Features**: Upgrade for analytics, bulk import, API integrations, and priority support.

## Pro Version

Unlock with $49/year:
- Unlimited coupons & auto-generator.
- Real-time analytics dashboard.
- WooCommerce/affiliate network integrations.
- Custom branding & A/B testing.

Contact: support@example.com

**Boost your affiliate earnings today!**
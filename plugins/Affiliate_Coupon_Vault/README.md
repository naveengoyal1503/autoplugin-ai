# Affiliate Coupon Vault

## Features

- **Automated Coupon Management**: Add, edit, and display affiliate coupons via admin panel or shortcodes.
- **Personalized Promo Codes**: Generate unique codes per visitor/user for higher conversions[1][2].
- **Click Tracking**: Track coupon clicks with AJAX (integrates with Google Analytics).
- **Shortcode Ready**: Use `[acv_coupon_box id="demo1"]` anywhere.
- **Responsive Design**: Mobile-friendly coupon boxes.
- **Freemium Model**: Free for up to 5 coupons; **Pro ($49/yr)**: Unlimited coupons, analytics dashboard, auto-expiry, custom branding, email capture.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons (JSON format).
4. Use shortcode `[acv_coupon_box id="your-coupon-id"]` in posts/pages.

## Setup

1. In admin: Add coupons as JSON array, e.g.:
   
   {
     "demo1": {
       "title": "50% Off",
       "code": "SAVE50",
       "description": "Exclusive deal.",
       "affiliate_link": "https://affiliate-link.com",
       "affiliate": "Brand",
       "expires": "2026-12-31"
     }
   }
   
2. Embed shortcode in Gutenberg or Classic Editor.
3. (Pro) Integrate with Stripe/PayPal for direct sales.

## Usage

- **Display Coupon**: `[acv_coupon_box id="demo1"]` shows styled box with copy button.
- **Tracking**: Clicks logged; add Google Analytics for advanced insights.
- **Monetization Tips**: Use for affiliate programs (Amazon, Bluehost). Personalized codes boost trust/conversions[1][2][3].
- **Pro Features**: Unlock via upgrade for full potential.

## FAQ

**Why coupons?** Plugins simplify affiliate tracking and sales[1][7].
**Limits?** Free: 5 coupons. Pro: Unlimited.

**Support**: example.com/support

*Built for 2026 monetization trends: Affiliates + personalized deals[2][3].*
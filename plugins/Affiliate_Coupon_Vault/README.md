# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited affiliate coupons via simple JSON in admin settings.
- **Shortcode Integration**: Use `[acv_coupons limit="5"]` to display randomized deals anywhere.
- **One-Click Generation**: AJAX button generates unique promo codes for exclusive deals.
- **Conversion Boost**: Drives clicks to affiliate links with eye-catching displays.
- **Freemium Model**: Free for basics; **Pro** adds analytics, unlimited coupons, custom branding ($49/year).
- **SEO-Friendly**: Clean markup for better search visibility.[1][2]

## Installation

1. Upload the plugin files to `/wp-content/plugins/affiliate-coupon-vault`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to configure coupons.

## Setup

1. In **Settings > Coupon Vault**, enter coupons as JSON array:
   
   [
     {"name":"10% Off","code":"SAVE10","affiliate_link":"https://aff.link","description":"Limited time"}
   ]
   
2. Save settings.
3. Add shortcode `[acv_coupons]` to any post/page.

## Usage

- **Display Coupons**: Paste shortcode in Gutenberg, Classic Editor, or widgets.
- **Generate Codes**: Click 'Generate New Coupon' on frontend for unique codes.
- **Customize**: Style via CSS in `wp_head` hook.
- **Pro Features**: Upgrade for click tracking, A/B testing, email integration.

## Monetization Tips

- Earn commissions from affiliate links.[3][4]
- Offer exclusive reader deals to build loyalty.[1][2]
- Upsell Pro via admin notice.

**Support**: Contact support@example.com
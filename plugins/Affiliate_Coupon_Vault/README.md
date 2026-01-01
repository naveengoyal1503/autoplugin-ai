# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited affiliate coupons via simple JSON in admin settings.
- **Shortcode Display**: Use `[acv_coupon id="0"]` to embed beautiful, responsive coupons anywhere.
- **Click Tracking**: Pro version tracks clicks, IPs, and analytics for optimization.
- **Conversion Boost**: Personalized coupons increase affiliate sales with custom codes and descriptions.
- **SEO-Friendly**: Coupons are schema-ready for better search visibility.
- **Freemium Model**: Free for basics, Pro ($49/year) unlocks unlimited coupons, advanced tracking, and priority support.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons.

## Setup

1. In **Settings > Coupon Vault**, enter JSON like:
   
   [
     {"name":"10% Off","code":"SAVE10","affiliate_url":"https://affiliate.com/?coupon=SAVE10","description":"Save on all products"}
   ]
   
2. Save and use shortcode `[acv_coupon id="0"]` (ID matches array index).

## Usage

- Embed coupons in posts, pages, or widgets.
- Customize appearance with CSS in the shortcode output.
- **Pro Upgrade**: For full tracking and unlimited features, purchase at example.com/pro.

## Pro Features
- Unlimited coupons
- Detailed click analytics dashboard
- Export reports
- Custom branding

Support: example.com/support
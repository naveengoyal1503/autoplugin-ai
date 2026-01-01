# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons (brand, code, discount %, affiliate URL) via simple settings page[1][2].
- **Shortcode & Widget Support**: Use `[acv_coupon id="0"]` or drag widget to sidebar for instant displays[3].
- **Click Tracking**: Tracks affiliate clicks with unique IDs for performance analytics (Pro feature)[1][3].
- **Personalized Promos**: Generate exclusive codes to boost conversions and reader loyalty[1][2].
- **Freemium Model**: Free for basics, Pro ($49/year) unlocks unlimited coupons, custom branding, and detailed reports[7].
- **SEO-Friendly**: Coupon blocks improve site stickiness and affiliate revenue[1][2].

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to add your coupons.

## Setup

1. In **Settings > Coupon Vault**, enter coupons in format: `Brand|CODE|50|https://your-affiliate-link.com` (one per line).
2. Example:
   
   Amazon|PRIME20|20|https://amzn.to/example
   Shopify|SAVE10|10|https://shopify.pxf.io/example
   
3. Use shortcode `[acv_coupon id="0"]` (replace 0 with coupon index) or add widget.
4. **Pro Upgrade**: Enable for advanced tracking via settings[7].

## Usage

- **Posts/Pages**: Embed `[acv_coupon id="1"]` for specific deals.
- **Sidebar**: Add widget for always-on coupon display.
- **Monetization**: Earn commissions on tracked clicks; personalize codes for brands[1][2][3].
- **Track Performance**: View logs in debug.log or upgrade to Pro dashboard.

**Pro Tip**: Target niches like software, travel, e-commerce for high conversions[2].

## Changelog

**1.0.0**
- Initial release with core features.
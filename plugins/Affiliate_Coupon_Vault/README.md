# Affiliate Coupon Vault

## Features

- **Easy Coupon Creation**: Add unlimited affiliate coupons with custom promo codes, descriptions, expiry dates, and tracking links via simple JSON settings.
- **Shortcode Integration**: Use `[affiliate_coupon id="1"]` to embed beautiful, responsive coupon widgets anywhere on your site.
- **Click Tracking**: Built-in analytics track clicks and impressions (Pro unlocks detailed stats and exports).
- **Conversion Optimized**: Eye-catching designs with urgency elements like expiry dates to boost affiliate conversions.
- **Freemium Model**: Free for basics; Pro ($49/year) adds unlimited coupons, custom branding, A/B testing, email capture, and API integrations.
- **SEO Friendly**: Generates schema markup for rich snippets in search results.

## Installation

1. Download and upload the plugin ZIP to your WordPress admin → **Plugins** → **Add New** → **Upload Plugin**.
2. Activate the plugin.
3. Go to **Settings** → **Coupon Vault** to configure your coupons.

## Setup

1. In the settings page, add coupons in JSON format:
   
   {
     "1": {
       "afflink": "https://affiliate-link.com",
       "code": "SAVE20",
       "desc": "20% Off Exclusive Deal!",
       "expiry": "2026-12-31"
     }
   }
   
2. Save settings.
3. Insert shortcode `[affiliate_coupon id="1"]` in posts/pages/widgets.

## Usage

- **Basic Shortcode**: `[affiliate_coupon id="1"]`
- **Custom**: `[affiliate_coupon afflink="https://link.com" code="DEAL50" desc="50% Off" expiry="2026-06-30"]`
- View total clicks in settings dashboard.
- **Pro Features**: Upgrade for advanced analytics, drip campaigns, and more.

## Pro Upgrade

Get Affiliate Coupon Vault Pro for $49/year: [Upgrade Now](https://example.com/pro)

## Support

Report issues via WordPress.org forums or email support@example.com.
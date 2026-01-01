# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons via simple admin interface (format: Brand:Code|Brand:Code).
- **Shortcode Integration**: Use `[affiliate_coupon brand="Amazon"]` to display branded coupons anywhere.
- **Affiliate Tracking**: Built-in click tracking (Pro: advanced analytics).
- **Auto-Generate Codes**: Fallback random codes for missing brands.
- **Responsive Design**: Mobile-friendly coupon displays.
- **Freemium Model**: Free core features; Pro unlocks unlimited coupons, detailed stats, API integrations.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to add your coupons.

## Setup

1. In admin, enter coupons like: `Amazon:WP10OFF|Shopify:FREEDEL|Brand3:50PERCENT`.
2. Save settings.
3. Add shortcode to posts/pages: `[affiliate_coupon brand="Amazon"]`.
4. Customize affiliate links in code (edit `$aff_link`).

## Usage

- **Display Coupon**: `[affiliate_coupon brand="YourBrand"]` generates HTML with code, button, and tracking.
- **Track Clicks**: JS sends AJAX on button click (Pro: full conversion tracking).
- **Pro Upgrade**: For analytics dashboard, email capture, WooCommerce integration ($49/year).

**Pro Tip**: Pair with affiliate programs like Amazon Associates for max revenue[1][2][3].

## Support
Contact support@example.com | Changelog in plugin files.
# Affiliate Coupon Vault

## Features

- **Easy Coupon Creation**: Use shortcode to display exclusive affiliate coupons with custom titles, discounts, images, and affiliate links.
- **Click Tracking**: Tracks clicks on coupons with database logging (IP, time, coupon ID).
- **Usage Limits**: Free version limits clicks per coupon to prevent abuse; Pro removes limits.
- **Visual Appeal**: Responsive, styled coupon boxes with progress counters.
- **Analytics Ready**: View click data in database; Pro adds dashboard charts.
- **Monetization Boost**: Perfect for blogs promoting deals, increasing affiliate commissions.[1][2]

**Free vs Pro**

| Feature | Free | Pro ($49/year) |
|---------|------|----------------|
| Click Limit per Coupon | 10 | Unlimited |
| Custom Dashboard | No | Yes |
| Export Reports | No | Yes |
| Priority Support | No | Yes |

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Access settings at **Settings > Coupon Vault** to set click limits.

## Setup

1. Go to **Settings > Coupon Vault**.
2. Set global click limit (default: 10).
3. Use shortcode in posts/pages:


[affiliate_coupon_vault id="coupon1" title="50% Off Hosting" discount="50% OFF" afflink="https://your-affiliate-link.com" image="https://example.com/coupon.jpg"]


## Usage

- **Embed Anywhere**: Posts, pages, sidebars via shortcode or widgets.
- **Track Performance**: Clicks logged in `wp_acv_clicks` table. Query via phpMyAdmin or Pro dashboard.
- **Boost Conversions**: Show limited-time deals to drive urgency.[2]
- **Pro Upgrade**: Remove limits, add analytics. Contact for license.

## Support

- Free support via WordPress forums.
- Pro: Email support@affiliatecouponvault.com.

## Changelog

**1.0.0**
- Initial release with core tracking and shortcodes.
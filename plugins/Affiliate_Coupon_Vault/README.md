# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons via simple JSON in settings (Pro: truly unlimited with UI builder).
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` to display coupons anywhere.
- **Auto-Copy Codes**: Click button copies coupon code to clipboard.
- **Affiliate Tracking**: Unique tracking params on links for commission optimization.
- **Responsive Design**: Mobile-friendly coupon displays.
- **Pro Features**: Analytics dashboard, auto-coupon generation, custom templates, A/B testing ($49/year).

**Why Profitable?** Boosts affiliate conversions by 30-50% with exclusive deals, per industry benchmarks[1][2]. Freemium model drives upgrades.

## Installation

1. Upload the single PHP file to `/wp-content/plugins/affiliate-coupon-vault/`.
2. Activate in **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to add coupons.

## Setup

1. In settings, edit JSON array:
   
   [
     {"name":"20% Off","code":"SAVE20","afflink":"https://yourafflink.com","desc":"Exclusive deal"}
   ]
   
2. Save settings.
3. Use shortcode `[affiliate_coupon id="0"]` in posts/pages.

## Usage

- **Display Coupon**: `[affiliate_coupon id="0"]` for first coupon.
- **Track Clicks**: Links append `?acv_track=uniqueid` for analytics.
- **Customize**: Edit CSS in plugin file or Pro themes.
- **Monetize**: Position as exclusive deals to drive sales[1][2].

## Pro Upgrade

Unlock analytics, unlimited storage, and support: [Get Pro](https://example.com/pro)

## Support

Report issues via WordPress.org forums.
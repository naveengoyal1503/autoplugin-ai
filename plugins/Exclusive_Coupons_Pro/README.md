# Exclusive Coupons Pro

## Features

- **Generate Exclusive Coupons**: Create unique, personalized promo codes for your audience.
- **Affiliate Integration**: Boost commissions with trackable affiliate links.
- **Shortcode Support**: Embed coupons anywhere with `[exclusive_coupon id="0"]`.
- **Admin Dashboard**: Easy management of coupons via JSON config.
- **Conversion Optimized**: Eye-catching designs encourage clicks and sales.
- **Freemium Model**: Free for basics, Pro unlocks unlimited coupons, analytics, auto-expiry, and custom branding ($49/year).

## Installation

1. Upload the `exclusive-coupons-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupons Pro** to configure your coupons.
4. Use shortcode `[exclusive_coupon id="0"]` in posts/pages.

## Setup

1. In **Settings > Coupons Pro**, edit the JSON coupons array:
   
   [
     {"name":"10% Off","code":"BLOG10","afflink":"https://your-affiliate.link","desc":"Exclusive deal"}
   ]
   
2. Save changes. Each coupon gets a unique code on display (e.g., BLOG10-abc123).
3. Embed via shortcode, specifying `id` (0-based index).

## Usage

- **Frontend**: Coupons display as styled boxes with unique codes and affiliate buttons.
- **Customization**: Add CSS to target `.exclusive-coupon` class.
- **Pro Features**: Analytics dashboard, bulk import, expiry dates, A/B testing.
- **Monetization Tip**: Partner with brands for custom codes to increase reader loyalty and commissions.

## Support

Contact support@example.com. Upgrade to Pro for priority help.
# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons via simple JSON in settings (Pro: truly unlimited).
- **Click Tracking**: Logs clicks and IP addresses in database for analytics.
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` to display coupons anywhere.
- **Expiration & Usage Limits**: Auto-handles max uses and expiry dates.
- **Affiliate-Friendly**: Redirects to your affiliate links after tracking.
- **Freemium Model**: Free for up to 5 coupons; Pro ($49/year) adds dashboard, auto-generation, email integrations.

## Installation

1. Upload the single PHP file to `/wp-content/plugins/affiliate-coupon-vault/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons in JSON format.

## Setup

1. In **Settings > Coupon Vault**, paste JSON like:
   
   [
     {"name":"10% Off","code":"SAVE10","affiliate_url":"https://youraffiliatelink.com","description":"Exclusive deal","uses":0,"max_uses":1000,"expires":"2026-12-31"}
   ]
   
2. Save. Use shortcode `[affiliate_coupon id="0"]` (id starts at 0).

## Usage

- Embed shortcodes in posts/pages.
- Visitors click "Get Deal & Track" → click logged → redirects to affiliate link.
- View stats in database or upgrade to Pro for dashboard.
- **Monetization Tip**: Offer custom coupons to brands for exclusive deals, boosting commissions.
# Affiliate Coupon Vault

## Features

- **Easy Coupon Display**: Use shortcode `[affiliate_coupon_vault]` to show exclusive deals.
- **Affiliate Tracking**: Built-in click tracking for commissions.
- **Customizable**: Filter by category, affiliate network (e.g., Amazon, others).
- **Admin Dashboard**: Add/edit coupons via JSON in settings.
- **Pro Upgrade**: Unlimited coupons, analytics, auto-generation, premium integrations ($49/year).

**Key Benefits**: Increases conversions with personalized coupons, positions your site as a deals hub.[1][2]

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to add your affiliate coupons (JSON format).

## Setup

1. In admin, paste JSON array of coupons:
   
   [
     {"title":"20% Off Electronics","code":"SAVE20","discount":"20%","link":"https://your-affiliate-link","category":"electronics","expires":"2026-12-31"}
   ]
   
2. Save settings.
3. Add shortcode to any post/page: `[affiliate_coupon_vault category="electronics" limit="5"]`.

## Usage

- **Shortcode Options**:
  - `affiliate`: Network (default: amazon)
  - `category`: Filter deals
  - `limit`: Number to show
- Embed in sidebar, posts, or pages for instant monetization.
- Track clicks with Google Analytics (GA4 recommended).

## Pro Features

- Unlimited coupons
- Deal auto-generator
- Conversion analytics
- Custom branding
- Premium affiliate APIs

**Monetization Ready**: Perfect for bloggers using affiliate marketing and coupons.[1][2][3]

## Support

Report issues in WordPress forums or upgrade to Pro for priority support.
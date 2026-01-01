# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited exclusive coupons via simple JSON in settings (Pro: truly unlimited).
- **Affiliate Tracking**: Each deal links to your affiliate URLs with built-in click tracking for Google Analytics.
- **Shortcode Display**: Use `[acv_coupons limit="5"]` to show coupons anywhere on your site.
- **Pro Upgrade**: $49/year unlocks unlimited coupons, advanced analytics dashboard, auto-expiry, and premium affiliate network integrations.
- **Conversion Boost**: Personalized deals increase clicks and commissions[1][2].

**Free vs Pro**

| Feature | Free | Pro ($49/yr) |
|---------|------|---------------|
| Coupons | 3 max | Unlimited |
| Analytics | Basic | Advanced Dashboard |
| Expiry | Manual | Auto |
| Integrations | None | Amazon, etc. |

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons.

## Setup

1. In **Settings > Coupon Vault**, paste JSON like:
   
   [
     {"name":"10% Off","code":"SAVE10","aff_link":"https://your-aff-link.com","desc":"Exclusive deal"}
   ]
   
2. Save settings.
3. Add `[acv_coupons]` shortcode to any post/page.

**Pro Activation**: Purchase upgrade, enter license key in settings.

## Usage

- **Display Coupons**: `[acv_coupons limit="3"]` for 3 latest deals.
- **Track Performance**: Links auto-track clicks (GA4 compatible).
- **Customization**: Style via CSS classes like `.acv-coupon`.
- **Monetization Tip**: Partner with brands for custom codes to boost conversions[1][2].

## Support

Report issues in WordPress.org forums. Pro users get priority email support.
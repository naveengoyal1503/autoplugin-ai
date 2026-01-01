# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited exclusive promo codes via simple JSON admin panel (Pro unlocks unlimited).
- **Shortcode Integration**: Use `[affiliate_coupon id="coupon1"]` to display coupons anywhere.
- **Affiliate Tracking**: Embeds nofollow links with affiliate branding for compliance.
- **Conversion Boost**: Eye-catching designs encourage clicks and sales.
- **Free Version**: Up to 3 coupons; Pro: Unlimited, analytics, custom CSS, auto-expiry.

**Pro Features**: Advanced analytics, email capture, A/B testing, premium integrations (Amazon, etc.). $49/year.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your first coupon.

## Setup

1. In admin, enter JSON like:
   
   {
     "coupon1": {
       "code": "SAVE20",
       "desc": "20% off all items",
       "link": "https://your-affiliate-link.com",
       "affiliate": "Amazon"
     }
   }
   
2. Save. Free version limits to 3 coupons.
3. Add shortcode to posts/pages: `[affiliate_coupon id="coupon1"]`.

## Usage

- **Display Coupons**: Use shortcode in Gutenberg blocks, widgets, or templates.
- **Customize**: Edit CSS in Pro or via child theme.
- **Monetize**: Earn commissions from clicks; track via affiliate dashboard.
- **Upgrade**: Visit [Pro Page](https://example.com/pro) for full features.

## FAQ

**How do I add more coupons?** Upgrade to Pro.
**Is it GDPR compliant?** Yes, no tracking cookies in free version.

Support: example@domain.com
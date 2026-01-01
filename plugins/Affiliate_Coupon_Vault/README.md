# Affiliate Coupon Vault

## Features

- **Generate Exclusive Coupons**: Create unique, personalized promo codes for each visitor to track conversions.
- **Easy Shortcodes**: Use `[affiliate_coupon name="example"]` to display coupons anywhere.
- **Affiliate-Friendly**: Append referral params to links automatically.
- **Admin Dashboard**: JSON-based coupon management (free: up to 5; Pro: unlimited).
- **Copy-to-Clipboard**: One-click code copying with JS.
- **Hover Effects**: Eye-catching coupon styling.
- **Freemium Model**: Free basic; **Pro ($49/yr)** adds analytics, auto-generation, custom branding, API integrations, and priority support.

**Pro Exclusive**: Conversion tracking, A/B testing, email capture for leads, and 50+ templates.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to add coupons in JSON format.
4. Use shortcode `[affiliate_coupon name="your-coupon-key"]` in posts/pages.

## Setup

1. In admin: Enter JSON like:
   
   {
     "amazon-deal": {
       "affiliate": "Amazon",
       "code": "SAVE20",
       "desc": "20% off electronics",
       "link": "https://amazon.com/deals"
     }
   }
   
2. Save and embed shortcode.
3. **Upgrade to Pro** for advanced features.

## Usage

- Embed shortcode in content.
- Visitors get unique codes (e.g., `SAVE20-abc123`).
- Track via affiliate dashboard.
- Customize styles in `/wp-content/plugins/affiliate-coupon-vault/assets/style.css`.

**Monetization Boost**: Personalized codes increase trust and conversions by 20-50% per industry benchmarks.

## Support

- Free: WordPress.org forums.
- Pro: Dedicated email + updates.
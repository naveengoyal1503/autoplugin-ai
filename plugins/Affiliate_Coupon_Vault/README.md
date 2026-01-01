# Affiliate Coupon Vault

## Features

- **Personalized Coupons**: Generates unique promo codes for each visitor (e.g., SAVE20-ABCD) to track conversions accurately.[1][2]
- **Easy Shortcodes**: Use `[affiliate_coupon id="amazon"]` or `[affiliate_coupon]` for random coupon.
- **Admin Dashboard**: JSON-based coupon management for affiliates like Amazon, Shopify.
- **Mobile-Responsive Design**: Eye-catching, conversion-optimized UI with gradients and hover effects.
- **Affiliate Tracking**: Appends site URL as ref parameter to links.
- **Freemium Upsell**: Premium unlocks unlimited coupons, analytics, auto-generation, custom branding ($49/year).

**Proven Profit Potential**: Coupon plugins like WP Coupons boost conversions; personalized codes improve partnerships and SEO.[1][2]

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Coupon Vault** to configure your affiliate coupons in JSON format.
4. Add shortcode to any post/page: `[affiliate_coupon]`. Done!

## Setup

1. In admin: Paste JSON like:
   
   {
     "amazon": {"code": "SAVE20", "link": "https://amazon.com/deal?ref=yourblog", "desc": "20% off"},
     "shopify": {"code": "BLOG10", "link": "https://shopify.com", "desc": "10% off"}
   }
   
2. Save. Test shortcode on frontend.
3. **Pro Tip**: Request custom codes from brands for exclusivity.[2]

## Usage

- **Posts/Blogs**: Embed coupons in reviews or deal roundups.
- **Sidebars/Widgets**: Use shortcode widget.
- **Random Mode**: `[affiliate_coupon]` rotates coupons for variety.
- **Tracking**: JS event for Google Analytics (GA4 compatible).

## Premium Features

- Unlimited coupons & brands.
- Click/conversion analytics dashboard.
- AI-powered coupon generation.
- Custom designs & A/B testing.

**Upgrade**: Visit example.com/premium ($49/year, billed annually).

## Support

- FAQ in plugin settings.
- Contact: support@example.com

**Monetization Ready**: Perfect for affiliate blogs – start earning commissions today![3][4]
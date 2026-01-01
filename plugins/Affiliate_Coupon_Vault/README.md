# Affiliate Coupon Vault

## Description
**Affiliate Coupon Vault** is a powerful WordPress plugin that helps bloggers and affiliate marketers display personalized coupons and deals to drive conversions and boost commissions. Easily manage coupons via admin settings and embed them anywhere with a shortcode.[1][2]

## Features
- **Easy Coupon Management**: Add/edit coupons (name, code, description, affiliate link, image, expiry) via JSON in settings.
- **Responsive Grid Display**: Beautiful, mobile-friendly coupon grid with shortcode `[affiliate_coupons limit="5"]`.
- **Affiliate-Friendly**: All links include `rel="nofollow"` and open in new tabs.
- **Expiry Tracking**: Automatically hides expired coupons.
- **Freemium Upsell**: Teaser for Pro version with API integrations and analytics.
- **Lightweight**: Single-file, no dependencies, SEO-optimized.[3][4]

## Installation
1. Download the plugin ZIP.
2. In WordPress admin: **Plugins > Add New > Upload Plugin**.
3. Activate the plugin.
4. Go to **Settings > Coupon Vault** to configure coupons.

## Setup
1. Navigate to **Settings > Coupon Vault**.
2. Paste JSON array of coupons in the textarea:
   
   [
     {"name":"10% Off Hosting","code":"HOST10","desc":"Get 10% off Bluehost","afflink":"https://your-aff-link","img":"https://example.com/host.jpg","expiry":"2026-12-31"}
   ]
   
3. Click **Save Changes**.

## Usage
- Embed on any page/post: `[affiliate_coupons limit="3"]`
- Customize limit (default: 5).
- Style via CSS classes: `.coupon-vault`, `.coupon-item`, `.coupon-btn`.
- **Pro Features** (coming soon): Auto-fetch deals from affiliate APIs, click tracking, A/B testing.

## Premium
Upgrade to Pro for $49/year: Unlimited coupons, analytics dashboard, 50+ affiliate network integrations. Visit [example.com/pro](https://example.com/pro).

## Support
Report issues on GitHub or contact support@example.com.

**Boost your affiliate earnings today!** 🚀
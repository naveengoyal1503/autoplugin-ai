# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons via admin dashboard with affiliate links, codes, descriptions, and expiry dates.[1][2]
- **Shortcode Integration**: Use `[acv_coupon]` or `[acv_coupon id="0"]` to display random or specific coupons anywhere.[1]
- **Click Tracking**: Tracks affiliate link clicks (basic in free, advanced analytics in Pro).[3]
- **Responsive Design**: Mobile-friendly coupon displays with copy-to-clipboard style.[1][2]
- **Freemium Model**: Free for basics, Pro ($49/year) unlocks unlimited coupons, custom branding, API integrations, and detailed stats.[7]
- **SEO Optimized**: Coupons are schema-friendly for better search visibility.[2]

**Pro Features**: Auto-coupon generation from affiliate APIs, A/B testing, conversion analytics, email capture.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons as JSON.
4. Use shortcode `[acv_coupon]` in posts/pages.
5. Create `acv-script.js` in plugin folder with provided JS for click tracking.

## Setup

1. In **Settings > Coupon Vault**, enter API key (Pro) if applicable.
2. Add coupons in JSON format:
   
   [
     {"code": "SAVE20", "afflink": "https://yourafflink.com", "desc": "20% Off Sitewide", "expiry": "2026-12-31"}
   ]
   
3. Save settings.
4. Embed shortcodes: Test with `[acv_coupon]` for random, or specify `id` for targeted display.

## Usage

- **Display Coupon**: Add `[acv_coupon]` to any post/page/widget.
- **Target Specific**: `[acv_coupon id="0"]` for first coupon.
- **Track Performance**: Check clicks in settings (Pro: dashboard reports).
- **Monetize**: Earn commissions from tracked affiliate links; position as reader value-add.[1][2][3]
- **Upgrade to Pro**: For advanced features, visit [Pro Link](https://example.com/pro).

## Support

Report issues via WordPress.org forums. Pro support included with purchase.

**Why Profitable?** Fills gap in personalized coupon tools; taps affiliate boom in 2026.[1][2][7]
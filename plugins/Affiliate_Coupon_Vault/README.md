# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited affiliate coupons via simple JSON in admin settings (Pro: Visual editor).
- **Click Tracking**: Tracks all coupon clicks with AJAX for performance analytics (Pro: Detailed dashboard).
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` anywhere to display coupons.
- **Responsive Design**: Mobile-friendly coupon displays with copy-to-clipboard code.
- **Monetization Ready**: Personalized promo codes boost conversions for affiliate links.[1][2]
- **Freemium Model**: Free for 5 coupons; Pro ($49/yr) unlocks unlimited, auto-expiration, A/B testing.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons in JSON format.
4. Use shortcode `[affiliate_coupon id="0"]` in posts/pages.

## Setup

1. In admin, enter coupons as JSON array:
   
   [
     {"name":"10% Off","code":"SAVE10","afflink":"https://affiliate.com/?ref=123","desc":"Exclusive deal!"}
   ]
   
2. Save and embed shortcode by ID (0,1,2...).
3. **Pro Tip**: Personalize codes for readers to improve conversions.[2]

## Usage

- **Display Coupon**: `[affiliate_coupon id="0"]` shows coupon #0.
- **Track Clicks**: Button redirects to affiliate link after logging click.
- **Customization**: Edit CSS/JS files or upgrade to Pro for themes.
- **Best Practices**: Place in sidebars, posts, or dedicated deal pages for max revenue.[1][3]

## Pro Version

- Unlimited coupons & categories.
- Analytics dashboard with conversion rates.
- Auto-generate unique codes.
- Integrations: WooCommerce, ARMember.[2][4]

**Upgrade: [Get Pro ($49/yr)](https://example.com/pro)**

## Support

Report issues on GitHub or contact support@example.com.
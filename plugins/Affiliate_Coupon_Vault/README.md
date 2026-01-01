# Affiliate Coupon Vault

## Features

- **Personalized Promo Codes**: Generates unique coupon codes per visitor (e.g., AFFHOST10-ABCD) for tracking and exclusivity[1][2].
- **Affiliate Link Tracking**: Logs clicks and coupons used, viewable in admin dashboard for conversion optimization.
- **Easy Shortcodes**: Use `[acv_coupon]` for random or `[acv_coupon id="0"]` for specific coupons.
- **Customizable Display**: Supports coupon images, titles, and branded buttons.
- **Freemium Ready**: Free version limits to 5 coupons; Pro unlocks unlimited, analytics export, and integrations.
- **SEO-Friendly**: Schema-ready markup for rich snippets on coupon pages.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons via JSON.

## Setup

1. In the admin page, enter coupons as JSON array:
   
   [
     {"name":"10% Off Hosting","code":"AFFHOST10","affiliate_url":"https://host.com/?ref=you","image":""},
     {"name":"Free Trial","code":"AFFTRIAL","affiliate_url":"https://tool.com/?ref=you","image":"https://example.com/trial.jpg"}
   ]
   
2. Save settings.
3. Add shortcode to any post/page: `[acv_coupon]`.

## Usage

- **Display Coupons**: Embed shortcodes in posts, sidebars, or widgets.
- **Track Performance**: View click stats in settings (Pro: detailed analytics).
- **Monetize**: Earn commissions via affiliate URLs; personalized codes boost conversions[1][2][3].
- **Pro Upgrade**: For unlimited coupons, A/B testing, email capture. Visit example.com/pro.

## Changelog

**1.0.0**
- Initial release with core tracking and shortcodes.

## Support

Report issues at example.com/support.
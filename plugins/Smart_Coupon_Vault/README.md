# Smart Coupon Vault

## Features
- **Easy Coupon Management**: Add, edit, and display affiliate coupons via admin panel and shortcodes.
- **Conversion-Optimized Display**: Eye-catching, mobile-responsive coupon boxes with copy-to-clipboard functionality.
- **Affiliate Tracking Ready**: Embed your affiliate links directly in coupons for seamless monetization.
- **Shortcode Support**: Use `[scv_coupon_display]` anywhere to show random or targeted coupons.
- **Pro Features** (Upgrade for $49/year): AI-powered coupon generation using OpenAI, analytics dashboard, unlimited coupons, custom branding, email capture integration.

## Installation
1. Upload the `smart-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to configure your coupons.

## Setup
1. In the admin settings, paste your coupons as JSON (see example below).
2. Example JSON:
   
   [
     {"code":"SAVE20","desc":"20% off","afflink":"https://your-affiliate-link.com","expiry":"2026-12-31"}
   ]
   
3. Replace `afflink` with your affiliate URLs.
4. Save settings.

## Usage
- Add `[scv_coupon_display]` shortcode to any post/page/sidebar.
- Customize via CSS classes: `.scv-coupon-vault`, `.scv-code`, `.scv-button`.
- **Pro Tip**: Place in blog posts, sidebars, or popups for max conversions. Track performance and upgrade for AI auto-generation.

## Support
Contact support@example.com. Upgrade to Pro for priority support and advanced features.
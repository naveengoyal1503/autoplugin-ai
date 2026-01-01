# Affiliate Coupon Vault

## Features

- **Automated Coupon Display**: Shortcode `[affiliate_coupon_vault]` generates beautiful coupon grids with affiliate links.
- **Customizable Coupons**: Add your own titles, codes, descriptions, and affiliate URLs via admin settings.
- **Categories & Limits**: Filter by category (hosting, tools, marketing) and set display limits.
- **Responsive Design**: Mobile-friendly styling included.
- **Pro Upgrade**: Unlimited coupons, auto-generation from affiliate APIs, click tracking, analytics dashboard ($49/year).

## Installation

1. Upload the plugin files to `/wp-content/plugins/affiliate-coupon-vault`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to configure coupons (JSON format).

## Setup

1. **Add Coupons**: In Settings > Coupon Vault, edit the JSON array. Example:
   
   [
     {"title":"10% Off","description":"Great deal!","code":"SAVE10","affiliate_link":"https://aff.link?ref=you","category":"hosting"}
   ]
   
2. Replace affiliate links with your own (Amazon, etc.).
3. Save changes.

## Usage

- Insert shortcode: `[affiliate_coupon_vault category="hosting" limit="3"]` in posts/pages.
- Example output: Coupon cards with code, description, and "Shop Now" button.
- **Pro Features**: Auto-fetch real-time coupons, conversion tracking, A/B testing.

## Support

Visit our site for pro version and support. Earn commissions effortlessly!

**Version 1.0.0 | Compatible with WordPress 6.0+**
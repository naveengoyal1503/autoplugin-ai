# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons (Pro) with name, code, discount, affiliate link, and expiry date.
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` to display coupons anywhere.
- **Copy-to-Clipboard**: One-click copy for coupon codes.
- **Expiration Handling**: Automatically hides expired coupons.
- **Affiliate Tracking Ready**: Pro version tracks clicks and conversions.
- **Freemium Model**: Free for up to 3 coupons; Pro unlocks unlimited features ($49/year).

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons.

## Setup

1. In **Settings > Coupon Vault**, add coupon details:
   - Name (e.g., "20% Off Hosting")
   - Code (e.g., "SAVE20")
   - Discount (e.g., "20%")
   - Affiliate Link (your tracked affiliate URL)
   - Expiry Date
2. Use shortcode `[affiliate_coupon id="0"]` (replace 0 with coupon index) in posts/pages.

## Usage

- **Display Coupon**: `[affiliate_coupon id="1"]` shows the second coupon.
- **Customization**: Style via CSS targeting `.acv-coupon`, `.acv-code`, `.acv-claim`.
- **Pro Upgrade**: For unlimited coupons, analytics, and custom branding, visit [Upgrade to Pro](https://example.com/pro).

## Support

Report issues or suggest features via WordPress.org forums.
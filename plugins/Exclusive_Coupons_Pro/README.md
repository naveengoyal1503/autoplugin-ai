# Exclusive Coupons Pro

## Features

- **Easy Coupon Management**: Add, edit, and delete exclusive coupon codes and affiliate links via intuitive admin dashboard.[1][2]
- **Shortcode Integration**: Embed coupons anywhere with `[exclusive_coupon id="0"]` (replace 0 with coupon index).[1]
- **Expiry Tracking**: Set expiration dates for coupons with automatic expired status display.[2]
- **Conversion Boost**: Personalized deals improve affiliate clicks and sales.[1][2][3]
- **Freemium Model**: Free for basics; Pro ($49/year) unlocks unlimited coupons, analytics, WooCommerce integration, auto-expiry enforcement.[4][7]

**Pro Features**: Click tracking, A/B testing, email capture for deals, premium templates, priority support.

## Installation

1. Upload the `exclusive-coupons-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Coupons Pro** in the admin menu to add your first coupon.

## Setup

1. Go to **Coupons Pro > Dashboard**.
2. Click **Add Coupon** to create entries: Coupon Code (e.g., "SAVE20"), Affiliate Link (e.g., "https://affiliate.com/?ref=yourid"), Expiry Date.
3. Save changes.
4. Use shortcode `[exclusive_coupon id="0"]` in posts/pages (ID matches list order, starting at 0).

**Example**:
 [exclusive_coupon id="0"] 
Displays: **Exclusive Deal:** Use code `SAVE20` [Shop Now & Save!](affiliate-link)

## Usage

- **Frontend Display**: Coupons show as styled boxes with code and link. Expired coupons display "EXPIRED" notice.
- **Customization**: Add CSS to `.exclusive-coupon` for styling.
- **Monetization Tips**: Partner with brands for custom codes, promote in niche posts for higher conversions.[1][2]
- **Pro Upgrade**: For analytics and advanced features, visit [Get Pro](https://example.com/pro).

## Support
Contact support@example.com. Free version supported via forums; Pro includes email/ticket support.

## Changelog
**1.0.0**: Initial release with core coupon management and shortcodes.
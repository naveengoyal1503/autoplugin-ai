# Smart Coupon Vault

## Features

- **Easy Coupon Management**: Add and manage exclusive coupons via simple JSON in admin dashboard.
- **Shortcode Display**: Use `[scv_coupon id="0"]` to display coupons anywhere.
- **Click Tracking**: Tracks affiliate link clicks for performance insights (Pro: Advanced analytics).
- **Responsive Design**: Mobile-friendly coupon boxes.
- **Freemium Model**: Free core features; Pro unlocks AI generation, unlimited coupons, exports, and white-label.

**Pro Features** (Upgrade for $49/year):
- AI-powered coupon code suggestions.
- Detailed analytics dashboard with conversion estimates.
- Unlimited coupons and custom branding.
- Import/Export CSV.

## Installation

1. Upload the `smart-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Coupons** in admin menu to add your coupons.

## Setup

1. In **Coupons** page, enter JSON array of coupons:
   
   [
     {"code":"SAVE20","desc":"20% off","afflink":"https://your-affiliate-link.com","expires":"2026-12-31"}
   ]
   
2. Save. Note the ID (array index) for shortcodes.

## Usage

- Add `[scv_coupon id="0"]` to any post/page/widget.
- Customize with CSS targeting `.scv-coupon`.
- Clicks redirect to affiliate links and are tracked.

## Support

Contact support@example.com. Upgrade to Pro for priority support.

## Changelog

**1.0.0**
- Initial release.
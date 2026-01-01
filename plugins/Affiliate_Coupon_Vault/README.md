# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited affiliate coupons via simple JSON in admin dashboard (free: 3 max)[1][2].
- **Shortcode Display**: Use `[acv_coupon id="0"]` to embed coupons anywhere[1].
- **Auto-Expiration**: Coupons expire automatically with date checks[2].
- **Affiliate Tracking**: Appends unique ref parameter to links[3].
- **Pro Upgrade**: Unlimited coupons, click analytics, custom templates, email capture ($49/year)[7].
- **Mobile-Responsive**: Styled boxes work on all devices.

**Monetization Ready**: Perfect for blogs earning via affiliates & exclusive deals[1][2][3].

## Installation

1. Upload `affiliate-coupon-vault.php` to `/wp-content/plugins/`.
2. Activate in **Plugins > Installed Plugins**.
3. Go to **Coupon Vault** menu, paste JSON coupons, save.
4. Add shortcode to posts/pages: `[acv_coupon id="0"]`.

## Setup

1. **Admin Dashboard**: Navigate to **Coupon Vault**.
2. **Add Coupons** (JSON format):
   
   [
     {"title":"10% Off Sitewide","code":"SAVE10","afflink":"https://affiliate-link.com","expires":"2026-12-31"},
     {"title":"Free Trial","code":"TRIAL2026","afflink":"https://other-aff.link"}
   ]
   
3. Save. Use `id` matching array index (0,1,...).

**Pro Tip**: Get custom codes from brands for exclusivity[2].

## Usage

- **Posts/Pages**: `[acv_coupon id="0"]` for first coupon.
- **Widget/Sidebar**: Add via Shortcode widget.
- **Dedicated Page**: Create `/coupons/` page with multiple shortcodes.
- **Tracking**: Links append `?ref=your-site-url` for affiliate dashboards[3].

## Pro Features

- Unlimited coupons & categories.
- Click/conversion analytics dashboard.
- Auto-generate SEO pages.
- Integration with email lists & memberships[4].

## FAQ

**Free limit reached?** Upgrade to Pro for unlimited[7].
**JSON help?** Simple array of objects with title/code/afflink/optional expires.

## Support

Report issues via WordPress.org forums. Pro support included with upgrade.

**Start monetizing today!** [Upgrade to Pro](https://example.com/pro)
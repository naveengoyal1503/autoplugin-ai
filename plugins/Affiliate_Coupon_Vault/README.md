# Affiliate Coupon Vault

## Description
**Affiliate Coupon Vault** is a powerful WordPress plugin that lets you create, manage, and display exclusive affiliate coupons directly on your site. Boost conversions with eye-catching coupon displays featuring promo codes, descriptions, and affiliate links. Perfect for bloggers, affiliate marketers, and deal sites.

## Features
- **Easy Coupon Management**: Add coupons via simple JSON in admin dashboard.
- **Shortcode Support**: Use `[acv_coupon]` for random or `[acv_coupon id="0"]` for specific.
- **Auto-Expiry Checks**: Coupons expire automatically based on date—no manual cleanup.
- **Responsive Design**: Mobile-friendly coupon boxes with copy-paste codes.
- **Pro Version**: Unlimited coupons, analytics dashboard, custom CSS, import/export, and API for dynamic deals.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Coupon Vault** in admin menu to add your first coupon.

## Setup
1. Navigate to **Coupon Vault** in your WordPress admin.
2. Enter coupons in JSON format:
   
   [
     {
       "title": "10% Off Sitewide",
       "code": "AFF10",
       "affiliate_link": "https://your-affiliate-link.com",
       "expiry": "2026-12-31",
       "description": "Exclusive deal for readers"
     }
   ]
   
3. Save and use shortcodes in posts/pages.

## Usage
- **Display a random coupon**: `[acv_coupon]`
- **Specific coupon**: `[acv_coupon id="0"]` (index from JSON array).
- Customize styles via CSS targeting `.acv-coupon`.

## Pro Upgrade
- Unlimited coupons (free limits to 5).
- Click analytics and conversion tracking.
- Custom branding and templates.
- One-click partner imports.

<a href="https://example.com/pro">Upgrade to Pro ($49/year)</a>

## Support
Contact support@example.com or visit our documentation.
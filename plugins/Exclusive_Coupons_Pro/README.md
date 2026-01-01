# Exclusive Coupons Pro

A powerful WordPress plugin to create, manage, and track exclusive affiliate coupons directly on your site. Boost conversions with personalized promo codes and analytics.

## Features

- **Easy Coupon Creation**: Add coupons with unique codes, affiliate links, descriptions, and expiration dates via simple admin interface.
- **Frontend Shortcode**: Display coupons with `[ecp_coupons]` or specific ones with `[ecp_coupons id="unique-id"]`.
- **Click Tracking**: Tracks coupon usage with IP logging and timestamps (stored in DB).
- **Auto-Expiration**: Coupons show as expired after set date.
- **Freemium Model**: Free for basics; Pro adds unlimited coupons, detailed analytics dashboard, custom designs, email integrations, and more.
- **SEO-Friendly**: Clean markup for better search visibility.

## Installation

1. Upload the `exclusive-coupons-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit **Settings > Coupons Pro** to create your first coupon.
4. Add `[ecp_coupons]` shortcode to any post/page.

## Setup

1. Go to **Settings > Coupons Pro**.
2. Fill in coupon details: Code (e.g., SAVE20), Affiliate URL, Description, Expiration (datetime-local).
3. Click **Add Coupon**.
4. Copy the shortcode for specific coupons: `[ecp_coupons id="unique-id"]`.

**Pro Upgrade**: Purchase at example.com for advanced features like revenue tracking and API support.

## Usage

- **Display All**: `[ecp_coupons]`
- **Single Coupon**: Use ID from admin list.
- **Customization**: Style via CSS classes like `.ecp-coupon`, `.ecp-use-coupon`.
- **Tracking**: Clicks redirect to affiliate URL after logging.

## Changelog

**1.0.0**
- Initial release with core features.

## Support

Contact support@example.com for help.
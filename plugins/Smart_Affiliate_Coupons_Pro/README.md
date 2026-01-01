# Smart Affiliate Coupons Pro

## Features

- **Easy Coupon Creation**: Add unlimited exclusive coupons via simple JSON in admin dashboard (free: up to 5; pro: unlimited).
- **Auto-Expiring Coupons**: Coupons expire automatically based on date.
- **Unique Tracking Links**: Append unique IDs to affiliate links for performance tracking.
- **Shortcode Integration**: Use `[sac_coupon]` or `[sac_coupon id="0"]` anywhere.
- **Responsive Design**: Mobile-friendly coupon displays.
- **Pro Features**: Analytics dashboard, email capture, A/B testing, WooCommerce integration, custom branding ($49/year).

## Installation

1. Upload the `smart-affiliate-coupons` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Affiliate Coupons** in the admin menu to add your coupons.

## Setup

1. In the plugin dashboard, edit the JSON textarea with your coupons:
   
   [
     {
       "code": "SAVE20",
       "afflink": "https://your-affiliate-link.com/?ref=blog",
       "desc": "20% Off Sitewide",
       "expires": "2026-12-31"
     }
   ]
   
2. Click **Save Coupons**.
3. Add shortcode to any post/page: `[sac_coupon]`.

## Usage

- **Display Random Coupon**: `[sac_coupon]`
- **Specific Coupon**: `[sac_coupon id="0"]` (index from array).
- **Track Performance**: Check error logs or upgrade to Pro for dashboard analytics.
- **Monetize**: Partner with brands for custom codes, embed in niche posts for higher conversions.

## Pro Upgrade

Unlock analytics, integrations, and support. Visit [pro link] (placeholder).

## Support

Report issues via WordPress.org forums.
# WP Exclusive Coupons Pro

## Features
- **Create unlimited exclusive coupons** with custom codes, affiliate links, and expiry dates (Pro).
- **Track usage** via AJAX without page reloads.
- **Shortcode support**: `[exclusive_coupon id="0"]` to display coupons anywhere.
- **Admin dashboard** for easy management.
- **Responsive design** with inline styles.
- **Freemium model**: Free for 5 coupons, Pro unlocks analytics, auto-expiry, and more ($49/year).

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress Admin > Plugins.
3. Go to **Coupons** menu to add coupons in JSON format.

## Setup
1. In **Coupons** admin page, edit the JSON textarea:
   
   {
     "0": {
       "name": "20% Off Hosting",
       "code": "WP20",
       "afflink": "https://your-affiliate-link.com",
       "expiry": "2026-12-31",
       "uses": 0
     }
   }
   
2. Save changes.
3. Use shortcode `[exclusive_coupon id="0"]` in posts/pages.

## Usage
- Embed coupons in blog posts for affiliate boosts.
- Track clicks and uses in real-time.
- **Pro Tip**: Pair with SEO plugins for "exclusive coupons" traffic.[1][2]

## Pro Upgrade
Get unlimited coupons, detailed analytics, email notifications, and priority support for $49/year.
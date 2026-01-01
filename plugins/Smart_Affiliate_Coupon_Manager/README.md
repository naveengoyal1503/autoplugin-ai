# Smart Affiliate Coupon Manager

## Features

- **Easy Coupon Creation**: Add custom coupons with affiliate links via simple JSON in admin settings.
- **Trackable Links**: Unique click IDs for each coupon redemption to track affiliate performance.
- **Shortcode Integration**: Use `[sacm_coupon id="0"]` to display coupons anywhere.
- **Conversion Booster**: Personalized discounts increase reader trust and sales.[1][2]
- **Freemium**: Free for up to 3 coupons; **Pro ($49/yr)**: Unlimited, analytics dashboard, auto-expiry, email capture.

## Installation

1. Upload the single PHP file to `/wp-content/plugins/smart-affiliate-coupon-manager/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Affiliate Coupons** in admin menu to add your coupons.

## Setup

1. In **Affiliate Coupons** page, enter JSON like:
   
   [
     {"name":"10% Off","code":"SAVE10","afflink":"https://aff.link/?ref=yourid","desc":"Exclusive deal"}
   ]
   
2. Save changes.
3. Add shortcode `[sacm_coupon id="0"]` to any post/page.

## Usage

- **Display Coupon**: Paste shortcode in Gutenberg, Classic Editor, or widgets.
- **Track Performance**: Check affiliate dashboard for `?cid=` parameters.
- **Pro Features**: Upgrade for Google Analytics integration, coupon limits removal, and A/B testing.

## Why Profitable?

Affiliate coupons drive higher conversions via personalized deals.[1][2] Monetize via freemium upgrades, targeting bloggers in niches like software, travel, eCommerce.[3]

**Support**: Contact support@example.com | **Pro Demo**: example.com/pro
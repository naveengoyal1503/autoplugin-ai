# Custom Affiliate Coupons Pro

## Description

**Custom Affiliate Coupons Pro** allows WordPress site owners to create and display exclusive, trackable coupon codes for affiliate marketing. Boost conversions by offering personalized discounts directly on your site.[^1][^2]

## Features

- **Easy Coupon Management**: Admin dashboard to add/edit coupons with affiliate links and descriptions.[^1]
- **Shortcode Display**: Use `[cac_coupon id="0"]` to embed coupons anywhere.[^2]
- **Click Tracking**: JavaScript-based tracking for affiliate performance.[^3]
- **Freemium Model**: Free for basics, Pro ($49/year) unlocks unlimited coupons, analytics, and expiration dates.[^7]
- **SEO-Friendly**: Custom coupons improve site value and engagement.[^1][^2]

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Affiliate Coupons** in admin menu to configure.[^4]

## Setup

1. In **Affiliate Coupons** page, enter coupons as JSON array:
   
   [
     {"code": "SAVE10", "afflink": "https://your-affiliate-link.com", "desc": "10% Off Sitewide"},
     {"code": "WELCOME20", "afflink": "https://another-link.com", "desc": "20% Off First Purchase"}
   ]
   
2. Save changes.
3. Use shortcodes like `[cac_coupon id="0"]` or `[cac_coupon id="1"]` in posts/pages.[^1][^5]

## Usage

- **Display Coupons**: Embed shortcode in blog posts, sidebars, or dedicated coupon pages.[^1]
- **Track Performance**: Pro version logs clicks and conversions for optimization.[^3]
- **Monetize**: Earn affiliate commissions from tracked clicks. Ideal for niches like software, travel, eCommerce.[^2][^3]
- **Pro Upgrade**: Visit [example.com/pro](https://example.com/pro) for advanced features.[^7]

## FAQ

**Is it free?** Yes, basic version is free. Pro for advanced features.[^7]

**How to get custom coupons?** Contact brands for exclusive codes.[^2]

## Changelog

**1.0.0** Initial release.
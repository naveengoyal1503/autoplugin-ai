# Affiliate Coupon Pro

## Features

- **Easy Coupon Management**: Add unlimited affiliate coupons, promo codes, and deals via simple JSON in admin settings.
- **Shortcode Integration**: Use `[affiliate_coupon id="0"]` to display coupons anywhere (posts, pages, widgets).
- **Conversion-Optimized Design**: Eye-catching boxes with copy-to-clipboard effects and trackable affiliate links.
- **SEO-Friendly**: Generates reader-first discount content for better engagement and search rankings.
- **Freemium Model**: Free for basics; **Pro ($49/year)** unlocks unlimited coupons, analytics dashboard, auto-expiry, custom branding, and premium integrations (Amazon, etc.).

## Installation

1. Upload the `affiliate-coupon-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Pro** to add your first coupon.

## Setup

1. In the admin page, enter coupons in JSON format:
   
   {
     "coupons": [
       {"code": "SAVE20", "afflink": "https://yourafflink.com", "desc": "20% Off Hosting"},
       {"code": "DEAL10", "afflink": "https://anotherlink.com", "desc": "$10 Off Software"}
     ]
   }
   
2. Save and use shortcode `[affiliate_coupon id="0"]` for the first coupon.

## Usage

- Embed in posts/pages with shortcodes.
- Customize styles via CSS.
- **Pro Users**: Access dashboard for click tracking and A/B testing.

## Pro Upgrade

Unlock full potential: [Buy Pro Now](https://example.com/buy-pro) - One-time $49 for lifetime updates and support.

## Support

Contact support@example.com for help.
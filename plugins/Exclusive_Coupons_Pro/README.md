# Exclusive Coupons Pro

## Features

- **Generate Exclusive Coupons**: Create custom discount codes with affiliate links for higher conversions.[1][2]
- **Shortcode Display**: Use `[exclusive_coupon id="0"]` to embed coupons anywhere.[5]
- **Expiration Tracking**: Auto-hide expired coupons.
- **Click Logging**: Basic tracking for performance insights.
- **Freemium Model**: Free for 5 coupons; Pro ($49/year) unlocks unlimited, analytics, automation.[7]
- **SEO-Friendly**: Positions your site as a deals hub for affiliate revenue.[1][2]

## Installation

1. Upload the `exclusive-coupons-pro.php` file to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Navigate to **Coupons Pro** in the admin menu to configure.

## Setup

1. In **Coupons Pro** menu, enter coupons in JSON format:
   
   [
     {
       "code": "SAVE20",
       "afflink": "https://your-affiliate-link.com",
       "desc": "20% off all products",
       "expires": "2026-12-31"
     }
   ]
   
2. Save changes.
3. Add shortcode to posts/pages: `[exclusive_coupon id="0"]` (index starts at 0).

## Usage

- **Frontend**: Coupons display with styled buttons linking to affiliates.
- **Customization**: Edit JSON for codes, links, descriptions, expiration.
- **Tracking**: Clicks logged to error log; Pro adds dashboard analytics.
- **Monetization Tip**: Pair with affiliate programs for commissions on sales via exclusive deals.[3][4]

## Pro Upgrade

- Unlimited coupons
- Advanced analytics & reports
- Auto-generation of codes
- Email notifications

Get Pro for $49/year: [Upgrade Link](https://example.com/pro)

## Support

Report issues via WordPress.org forums.
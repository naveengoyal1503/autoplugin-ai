# Affiliate Coupon Booster

## Description
Affiliate Coupon Booster is a powerful WordPress plugin that enables affiliate marketers, bloggers, and e-commerce sites to create, manage, and display affiliate-linked coupons and deals efficiently. It automatically tracks click-throughs to maximize affiliate commissions and improve conversions with minimal setup.

## Features

- Custom post type for easy coupon creation with title, description, coupon code, affiliate link, and expiry date.
- Frontend shortcode `[acb_coupons]` to display active coupons elegantly.
- Click tracking with AJAX to record coupon claim clicks for better affiliate performance analysis.
- Supports coupon codes display for easy copying by users.
- Responsive and simple design, customizable via CSS.
- No configuration needed to start.

## Installation

1. Upload the `affiliate-coupon-booster.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Create new Coupons via the WordPress admin menu «Coupons».
4. Add coupon details including affiliate link and coupon code.
5. Insert the shortcode `[acb_coupons]` into any post or page where you want to show coupons.

## Setup

1. Navigate to the new "Coupons" menu in your WordPress dashboard.
2. Click "Add New" to create a new coupon.
3. Enter a title, description, coupon code, and the affiliate URL.
4. (Optional) Set an expiry date using a custom field named `expiry_date` (format YYYY-MM-DD).
5. Publish the coupon.
6. Use the `[acb_coupons]` shortcode in pages or widgets to display all published coupons.

## Usage

- Place the shortcode `[acb_coupons]` in posts, pages, or widgets to display a list of coupons.
- Visitors click "Claim Deal" buttons which redirect through tracking to your affiliate URLs.
- Use the click tracking data (stored transiently) to understand coupon popularity (extendable).
- Style and customize the look via the included CSS or your theme.

## FAQ

**Q: Can I add coupons with just affiliate links and no coupon codes?**

A: Yes, the coupon code field is optional. Coupons can work just with the affiliate link.

**Q: How can I see analytics for clicks?**

A: Currently, clicks are stored transiently server-side. For advanced analytics, integration with Google Analytics or a custom dashboard would be needed.

**Q: Can I limit coupons by categories or tags?**

A: The current version doesn't support categories. This could be added in future updates.

---

© 2025 Affiliate Coupon Booster Plugin by GeneratedAI
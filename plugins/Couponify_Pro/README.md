# Couponify Pro

## Description
Couponify Pro allows you to create and manage exclusive, user-submitted coupon codes integrated with optional affiliate links to boost your earnings and site engagement.

## Features
- Custom post type for coupons
- Frontend submission form with nonce security
- Supports coupon code, description, and optional affiliate link
- Lists recent coupons on any page with shortcode
- Admin interface to manage coupons
- Prevent duplicate coupon codes
- Track affiliate links tied to coupons

## Installation
1. Upload the `couponify-pro.php` file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use shortcode `[couponify_form]` to display the coupon submission form on any post/page.
4. Use shortcode `[couponify_list]` to display a list of recent coupons.

## Setup
- Navigate to the 'Couponify' menu in WordPress admin to manage coupons.
- Coupons submitted via the frontend form are immediately published.

## Usage
- Visitors can submit their own coupon codes with optional affiliate URLs.
- Display submitted coupons anywhere to attract visitors looking for deals.
- Monetize by promoting affiliate links embedded in coupons.

## Shortcodes
- `[couponify_form]` - Show coupon submission form
- `[couponify_list]` - Show list of recent coupons

## FAQ
**Q:** Can I customize the look of the coupon list?

**A:** Yes, you can style `.couponify-coupon-list` and its child elements using your theme's CSS.

**Q:** Are affiliate links tracked?

**A:** Currently, affiliate links are stored and displayed but not tracked for clicks. Future updates may add click tracking.

## Support
For support, please open an issue on the plugin repository or contact the author.

## License
This plugin is licensed under GPLv2 or later.
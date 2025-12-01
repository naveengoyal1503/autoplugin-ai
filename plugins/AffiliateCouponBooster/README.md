# AffiliateCouponBooster

AffiliateCouponBooster is a WordPress plugin designed to boost your affiliate sales by aggregating and displaying exclusive coupons and deals dynamically. Ideal for affiliate marketers, niche bloggers, and ecommerce sites.

## Features

- Display a customizable, easy-to-manage list of coupons with codes and affiliate URLs.
- Simple JSON-based coupon management in the admin area.
- Responsive and clean coupon display using shortcode.
- Freemium-ready for future expansion with premium add-ons.

## Installation

1. Upload the plugin PHP file to your `/wp-content/plugins/` directory or install via WordPress admin plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'AffiliateCouponBooster' menu in the admin dashboard.

## Setup

1. Access the AffiliateCouponBooster settings page.
2. Enter your coupons as a JSON array with the following fields:
   - `title` - Name of the coupon/deal
   - `description` - Short details about the coupon
   - `code` - Coupon code users can apply
   - `affiliate_url` - Affiliate product link

Example:

[
  {
    "title": "10% off Widget A",
    "description": "Save 10% on Widget A",
    "code": "WIDGET10",
    "affiliate_url": "https://affiliate.example.com/product/widget-a"
  }
]


3. Save changes.

## Usage

- Add the shortcode `[acb_coupons]` to any post or page where you want the coupon list to appear.
- Coupons will display with title, description, coupon code, and a styled affiliate link.

## Future Enhancements

- Automatic coupon fetching from major affiliate networks.
- Premium analytics dashboard.
- Scheduled coupon expiry and alerts.
- Multi-layout display options.

## Support

Contact the plugin author via the support forum or website.

## License

GPL v2 or later
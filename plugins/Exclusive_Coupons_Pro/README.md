# Exclusive Coupons Pro

## Features

- **Generate Unique Coupons**: Automatically creates personalized discount codes for each visitor (e.g., SAVE20-abc123).
- **Easy Shortcode Integration**: Use `[exclusive_coupon id="0"]` to display coupons anywhere.
- **Admin Dashboard**: Manage coupons via simple JSON format in Settings > Coupons Pro.
- **Affiliate-Ready**: Append unique codes to affiliate links for tracking conversions.
- **Copy-to-Clipboard**: One-click code copying with JS.
- **Premium Upsell**: Unlock analytics, auto-expiration, unlimited coupons, and more ($49/year).

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via Plugins > Installed Plugins.
3. Go to Settings > Coupons Pro to add your coupons.

## Setup

1. In admin, enter coupons as JSON array:
   
   [
     {"name":"Amazon","code":"AMZ20","url":"https://your-affiliate-link.com/?ref=site","desc":"20% Off"},
     {"name":"BrandX","code":"BX50","url":"https://aff-link.com","desc":"$50 Off"}
   ]
   
2. Save. Use shortcode with `id` matching array index (0,1,...).

## Usage

- **Posts/Pages**: Add `[exclusive_coupon id="0"]` for first coupon.
- **Widgets/Sidebar**: Use shortcode widget.
- **Customization**: Style via CSS targeting `.ecp-coupon` classes.
- **Tracking**: Unique codes enable precise affiliate attribution.

Boost conversions with exclusive deals! Upgrade to Pro for advanced features.
# Exclusive Coupons Pro

A powerful WordPress plugin to generate and display exclusive affiliate coupons, boosting conversions and revenue for bloggers and marketers.[1][2]

## Features
- **Generate unique coupon codes** automatically with personalized suffixes for tracking.
- **Easy shortcode integration**: `[exclusive_coupon id="0"]` to display coupons anywhere.
- **Admin dashboard** for managing unlimited coupons (JSON format for flexibility).
- **Copy-to-clipboard** functionality and one-click reveal deals with affiliate links.
- **Responsive design** with attractive styling out-of-the-box.
- **Freemium model**: Premium unlocks analytics, custom branding, API integrations, and unlimited coupons.

## Installation
1. Upload the `exclusive-coupons-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupons Pro** to configure your coupons.

## Setup
1. In the admin settings, enter coupons as JSON array:
   
   [
     {"name":"20% Off Hosting","code":"HOST20","afflink":"https://affiliate.com/host","desc":"Exclusive deal for readers"}
   ]
   
2. Save settings.
3. Use shortcode `[exclusive_coupon id="0"]` (replace `0` with coupon index).

## Usage
- **On posts/pages**: Add shortcode to display interactive coupon boxes.
- **Customization**: Edit styles via `wp_head` or enqueue custom CSS/JS.
- **Tracking**: Unique codes enable conversion attribution.
- **Monetization**: Earn via affiliates; upgrade to Pro for advanced tracking ($49/year).

## Premium Features
- Click/session analytics.
- Custom coupon expiration.
- Import/export coupons.
- WooCommerce integration.

Support: Contact support@example.com. Upgrade: example.com/premium
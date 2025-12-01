# Affiliate Coupon Booster

## Description
Affiliate Coupon Booster automatically displays optimized affiliate coupon codes and deals from a simple JSON configuration, helping affiliate marketers and bloggers boost conversions and earn higher commissions.

## Features
- Add and manage coupon codes directly from the WordPress admin
- Display random or rotating affiliate coupons on any post or page using a shortcode
- Supports unlimited coupons with affiliate URLs
- Clean, responsive coupon box design
- Simple JSON format for easy coupon management
- Lightweight and self-contained in a single PHP file

## Installation
1. Upload the `affiliate-coupon-booster.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Affiliate Coupons' admin menu to add your coupon codes in JSON format.

## Setup
- Enter your coupon data as a JSON array in the admin settings. Example format:
  
  [
    {
      "code": "SAVE10",
      "description": "Save 10% at Store",
      "affiliate_url": "https://affiliatelink.com/product?affid=123"
    },
    {
      "code": "FREESHIP",
      "description": "Free Shipping on orders over $50",
      "affiliate_url": "https://affiliatelink.com/freeshipping?affid=123"
    }
  ]
  

## Usage
- Use the shortcode `[affiliate_coupon_boost]` in any post or page to display one random coupon from the list.
- Style and placement can be customized by overriding the CSS in your theme if desired.

## Monetization
- Freemium model with basic coupon display free.
- Pro version can include multiple display modes, analytics integration, scheduled coupon rotation, and multi-network affiliate management.

Enjoy boosting your affiliate revenue with Affiliate Coupon Booster!
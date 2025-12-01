# Smart Affiliate Coupons

## Description

Smart Affiliate Coupons is a WordPress plugin designed to help bloggers, eCommerce store owners, and affiliate marketers easily create, manage, and display dynamic coupon codes and affiliate deals. It supports creating niche-specific offers to increase conversions and user engagement.

## Features

- Simple admin interface to manage coupons and affiliate deals as JSON
- Display active coupons on any post or page with a shortcode `[sac_coupons]`
- Supports coupon code, description, and affiliate URL for each deal
- Mobile-friendly and visually clean coupon list output
- Freemium-ready: core features available in free version; potential for premium upgrades like analytics, scheduling, and personalization

## Installation

1. Download the `smart-affiliate-coupons.php` file.
2. Upload it to your WordPress plugin directory `/wp-content/plugins/`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Navigate to the **Smart Coupons** menu in the admin dashboard.
5. Enter your coupon codes and affiliate deals in JSON format.

## Setup

Example JSON format to input in settings:


[
  {
    "code": "SAVE10",
    "desc": "10% off on selected items",
    "url": "https://example.com/shop?ref=affiliate"
  },
  {
    "code": "FREESHIP",
    "desc": "Free Shipping on orders over $50",
    "url": "https://example.com/cart?ref=affiliate"
  }
]


## Usage

- Place the shortcode `[sac_coupons]` on any page or post where you want to show the active coupons.
- Coupons will display with their codes, descriptions, and clickable buttons linking to the affiliate URLs.

## Monetization

The plugin uses a **freemium** model:

- Free: Basic coupon management and display
- Premium (planned): Scheduled coupons, coupon A/B testing, detailed usage analytics, personalized deals based on user behavior

This scalable model allows plugin authors to start with a free version and expand monetization as users grow.

---

This plugin is a unique, self-contained profitable WordPress monetization tool tailored for affiliate marketers and eCommerce sites wanting to boost conversions using dynamic coupon management.
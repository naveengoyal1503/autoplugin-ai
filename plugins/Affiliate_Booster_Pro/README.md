# Affiliate Booster Pro

Affiliate Booster Pro is a powerful WordPress plugin designed to **help bloggers and affiliate marketers manage affiliate links, display smart coupons, and track clicks** to boost affiliate income effortlessly.

## Features

- Manage and highlight affiliate domains within your content automatically.
- Display coupon codes and deals via an easy shortcode.
- Track clicks on affiliate links with detailed analytics in the admin panel.
- Simple admin interface to add affiliate domains and manage coupons.
- Lightweight front-end scripts minimize performance impact.
- Freemium-ready: Core functionality free, with scope for premium add-ons.

## Installation

1. Upload `affiliate-booster-pro.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Go to the Affiliate Booster menu in your dashboard to configure settings.

## Setup

- In the settings page, input your **affiliate domains** separated by commas. These domains will be recognized in your content and affiliate links enhanced.
- Add your **coupons** using JSON format with the following structure:

[
  {"code": "SAVE10", "url": "https://example.com/product?aff=123", "desc": "Save 10% on selected items"},
  ...
]


## Usage

- Use the shortcode `[affiliate_booster_coupon]` anywhere in posts or pages to display your coupon offers.
- Affiliate links detected in your content for the specified domains will be automatically enhanced with tracking.
- View click analytics on the Affiliate Booster admin page to monitor affiliate link performance.

---

Optimize your affiliate efforts, increase conversions, and monitor results all in one plugin with Affiliate Booster Pro!
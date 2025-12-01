# SmartAffiliateManager

## Description
SmartAffiliateManager is a powerful and easy-to-use WordPress plugin that enables site owners to manage and optimize their affiliate programs. With AI-driven link optimization and built-in performance analytics, it helps increase affiliate revenue and track conversions seamlessly.

## Features
- Manage multiple affiliate programs in one place
- Add, edit, activate/deactivate affiliate links via JSON in admin panel
- Shortcode support to easily insert affiliate links anywhere `[sam_affiliate_link name="BrandName"]`
- Click logging and basic analytics dashboard
- AI-driven affiliate link optimization (premium feature planned)
- Multi-tier affiliate program support (premium feature planned)
- Easy integration with any WordPress theme

## Installation
1. Upload `smart-affiliate-manager.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Smart Affiliate Manager' menu in your admin dashboard
4. Add your affiliate programs in JSON format (see example below)

## Setup

Add affiliate programs using JSON, for example:


[
  {
    "name": "BrandA",
    "url": "https://affiliate.branda.com/?ref=123",
    "active": true,
    "default_commission": 10
  },
  {
    "name": "BrandB",
    "url": "https://affiliate.brandb.com/ref=456",
    "active": true,
    "default_commission": 15
  }
]


## Usage
- Use shortcode `[sam_affiliate_link name="BrandName"]` in posts, pages, or widgets to output affiliate links.
- Monitor click analytics directly on the plugin settings page.
- Activate or deactivate affiliates by toggling the `active` field in the JSON data.

## Monetization
The plugin operates on a freemium model:
- Free core features for affiliate management and basic analytics
- Premium subscription unlocks AI optimization, detailed multi-tier analytics, and priority support

---

Created with an aim to boost affiliate marketing revenue through smart management and data-driven optimization.
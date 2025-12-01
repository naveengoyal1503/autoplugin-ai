# WP Deal Vault

WP Deal Vault is a WordPress plugin for bloggers, affiliate marketers, and ecommerce websites to easily aggregate and display affiliate coupons, flash deals, and discounts. The plugin automates deal expiration and allows manual management of deal data in JSON format.

## Features

- Simple JSON-based deal management in the admin panel.
- Frontend shortcode `[wp_deal_vault]` to display active deals responsively.
- Automatic removal of expired deals via scheduled hourly cron.
- Affiliate-friendly: links open in new tabs with nofollow and noopener.
- Lightweight, self-contained single PHP file plugin.

## Installation

1. Upload the `wp-deal-vault.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress admin Plugins page.
3. Navigate to "WP Deal Vault" menu to add or edit your deals in JSON format.

## Setup

- Enter your deals as a JSON array of objects with keys: `title`, `link`, `description`, and `expiry` (YYYY-MM-DD).
- Example:

[
  {"title": "Black Friday Deal", "link": "https://affiliate.link/blackfriday", "description": "50% off on all products!", "expiry": "2025-12-10"},
  {"title": "Cyber Monday", "link": "https://affiliate.link/cyber", "description": "Exclusive Cyber Monday 40% off", "expiry": "2025-12-02"}
]


## Usage

- Use shortcode `[wp_deal_vault]` in posts or pages to display current active deals.
- Deals with expiry date past today are automatically removed hourly.
- You can style deals using `.wp-deal-vault` and `.wp-deal-item` CSS classes.

## Monetization

Easily monetize your WordPress site by promoting affiliate deals and coupons.
Offer a free version of your deal aggregation with optional premium add-ons like:
- Automatic affiliate link cloaking
- Deal import from popular coupon APIs
- Deal analytics dashboard
- Scheduled social media sharing of deals

This plugin empowers content creators to drive affiliate commissions seamlessly while offering visitors valuable savings.
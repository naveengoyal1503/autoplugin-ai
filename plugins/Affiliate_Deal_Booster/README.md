# Affiliate Deal Booster

Affiliate Deal Booster is a WordPress plugin that automatically collects and displays affiliate coupons, discount codes, and deals from multiple external affiliate data sources in real time. It enhances your affiliate marketing efforts by boosting user engagement and conversions with a customizable, easy-to-use deal showcase.

## Features

- Aggregates affiliate deals and coupons from multiple JSON API sources
- Automatically caches deals to reduce load and maintain speed
- Admin panel to specify affiliate data sources (API URLs) and caching duration
- Shortcode `[affiliate_deals]` to embed deal listings anywhere on your site
- Simple yet clean, customizable deal display
- AJAX refresh button in admin for on-demand manual update
- Lightweight single-file plugin for easy deployment

## Installation

1. Upload the `affiliate-deal-booster.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the WordPress admin sidebar, find and open 'Affiliate Deal Booster'.
4. Enter one or more affiliate source URLs that return JSON-formatted deals (one URL per line).
5. Set cache duration in minutes to control how often deals refresh.
6. Save settings.
7. Use the shortcode `[affiliate_deals]` in pages, posts, or widgets to show the latest deals.

## Setup

- Your affiliate sources should provide deal data in a JSON array format with objects containing at least: `title`, `url`, and `description` fields.
- Example JSON response from an affiliate source:

[
  {
    "title": "30% off Summer Shoes",
    "url": "https://affiliate.example.com/deal123",
    "description": "Use code SUMMER30 at checkout."
  },
  {
    "title": "Free Shipping on Orders over $50",
    "url": "https://affiliate.example.com/deal456",
    "description": "Automatically applied at checkout."
  }
]

- After saving your affiliate sources, the plugin will fetch and cache deals.

## Usage

- Insert the shortcode `[affiliate_deals]` anywhere in your content to display the currently cached deals.
- You can manually refresh deals from the admin settings page by clicking the 'Refresh Deals Now' button.
- Use CSS if desired to style the deals list (`.adb-deal-list` class).

---

Affiliate Deal Booster helps you monetize your WordPress site by displaying timely affiliate deals with minimal manual overhead. Boost your affiliate commissions by providing visitors with valuable, curated discounts and offers.

_For support, please visit the plugin's support forum or contact the author._
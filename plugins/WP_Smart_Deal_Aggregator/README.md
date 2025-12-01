# WP Smart Deal Aggregator

## Description
WP Smart Deal Aggregator automatically aggregates coupons and deals from multiple affiliate feeds, appends your affiliate ID for commission tracking, and displays them in customizable widgets or via shortcode. It is designed to help bloggers, deal sites, and affiliate marketers monetize their WordPress sites easily.

## Features

- Fetch deals from multiple affiliate JSON feeds automatically
- Append your affiliate ID to deal links for tracking
- Cache deals for performance (updates hourly via WP Cron)
- Display deals with a simple shortcode `[wpsda_deals]`
- Admin settings to add affiliate feeds, affiliate ID, and control deal display limit
- Lightweight and self-contained in a single PHP file

## Installation

1. Upload `wp-smart-deal-aggregator.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings → Smart Deal Aggregator** to configure your affiliate ID and add deal feed URLs.

## Setup

- Enter your **Affiliate ID** to be appended to all deal links.
- Paste one or more affiliate feed URLs (each returning deals in JSON format) — one per line.
- Set the max number of deals to show with the shortcode.

## Usage

- Insert the shortcode `[wpsda_deals]` in any post, page, or widget to display the curated deals.
- The plugin automatically refreshes deals hourly to keep your offers updated.

## Example JSON Feed Format
Your feeds should provide an array of deals like:


[
  {
    "title": "50% off on Shoes",
    "url": "https://example.com/deal/shoes",
    "description": "Limited time offer.",
    "expiry_date": "2025-12-31"
  },
  {
    "title": "Buy one get one free",
    "url": "https://example.com/deal/bogo",
    "description": "Great deal for summer",
    "expiry_date": ""
  }
]


## Support
For questions or feature requests, please open an issue on the plugin repository or contact support.

## License
GPL v2 or later.
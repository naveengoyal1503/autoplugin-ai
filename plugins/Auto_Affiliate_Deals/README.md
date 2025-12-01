# Auto Affiliate Deals

Automatically fetch and display niche-specific affiliate coupons and deals on your WordPress site with a simple shortcode.

## Features

- Connects to an affiliate deals API with your API key
- Fetches relevant coupons and deals based on your keyword
- Caches deals for 1 hour to improve performance
- Display deals anywhere with `[auto_affiliate_deals]` shortcode
- Simple admin settings page to configure API key and search keyword

## Installation

1. Upload `auto-affiliate-deals.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Auto Affiliate Deals** to enter your affiliate API key and the keyword for fetching deals.

## Setup

- Obtain an API key from your affiliate deals provider.
- Enter the API key and the search keyword related to your niche in the settings page.
- Save your settings.

## Usage

- Use the shortcode `[auto_affiliate_deals]` in posts, pages, or widgets to display the latest deals automatically.

Example:

html
[auto_affiliate_deals]


The plugin will display a styled list of deals with clickable links and coupon codes if available.

---

*Note: This plugin requires a valid affiliate network API to fetch deals. Replace the placeholder API endpoint with your real affiliate API URL.*
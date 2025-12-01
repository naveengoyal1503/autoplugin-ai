# Affiliate Deal Booster

Affiliate Deal Booster is a simple but powerful WordPress plugin that lets you display affiliate discount deals and coupons fetched from multiple networks and track users' clicks to help you maximize affiliate sales and revenue.

## Features

- Easily add and manage affiliate deals via admin settings using JSON format
- Display affiliate deals anywhere using the `[affiliate_deals]` shortcode
- Automatically track and record affiliate link clicks for performance insights
- Lightweight and developer-friendly
- Support for multiple affiliate deals with title, URL, and description

## Installation

1. Upload the plugin PHP file to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings > Affiliate Deal Booster** to add your affiliate deals in JSON format
4. Use the shortcode `[affiliate_deals]` to display deals on posts, pages, or widgets

## Setup

- Format your affiliate deals as a JSON array with objects containing `title`, `url`, and `description`, for example:


[
  {
    "title": "10% off on Awesome Product",
    "url": "https://affiliate-network.com/product?ref=yourid",
    "description": "Save 10% with this exclusive offer"
  },
  {
    "title": "Free shipping on orders over $50",
    "url": "https://affiliate-network.com/shipping-deal?ref=yourid",
    "description": "Get free shipping instantly"
  }
]


- Paste this JSON in the settings page textarea and save

## Usage

- Insert the shortcode `[affiliate_deals]` in any post, page, or widget where you want the deals to appear.
- Visitors clicking on the deals will be tracked automatically for click counts.
- Monitor click counts via the WordPress database option `adb_click_data` (requires database access or developer tools).

## Monetization

The plugin is suited for a freemium model:

- Free tier allowing manual deal input and basic display
- Premium upgrades could include multi-network API integration for automatic fetching, detailed analytics dashboard for clicks and conversions, styling customization, and priority support.

## Support

If you encounter issues or have feature requests, please open a ticket on the plugin support forum or contact the author.

---

Build your affiliate revenue smartly and efficiently with Affiliate Deal Booster!
# Affiliate Deal Booster

Affiliate Deal Booster is a WordPress plugin designed to help affiliate marketers, bloggers, and e-commerce sites automatically feature and highlight the best affiliate coupons and deals directly within their blog posts to increase conversions and revenue.

## Features

- Automatically append exclusive affiliate deals and coupons to your posts.
- Simple admin interface to enter and manage multiple affiliate deals in JSON format.
- Displays only unexpired deals with clickable affiliate links.
- Clean and customizable deals box styling.
- Freemium-ready architecture for future automated deal discovery and analytics.

## Installation

1. Upload the `affiliate-deal-booster.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Affiliate Deal Booster** to configure your deals.

## Setup

- In the plugin settings page, enter your affiliate deals as a JSON array. Each deal requires these fields: `title`, `url`, and `expires` (YYYY-MM-DD).
- Save changes to apply the deals sitewide.
- Deals will automatically show at the bottom of every single post.

## Usage

- Write or edit your blog posts as usual.
- The plugin automatically enhances posts by appending the active affiliate deals block.
- Update deals anytime in the plugin settings without editing posts.

Example deals JSON:


[
  {
    "title": "Save 20% on Product A",
    "url": "https://affiliate.example.com/product-a?ref=123",
    "expires": "2025-12-31"
  },
  {
    "title": "Get $10 off your order",
    "url": "https://affiliate.example.com/offer?ref=123",
    "expires": "2025-11-30"
  }
]


## Support

For support or feature requests, visit the plugin homepage or contact the author.

## License

GPLv2 or later.
# Smart Affiliate Deal Booster

Smart Affiliate Deal Booster helps you boost affiliate conversions by personalized deal popups and banners triggered by visitor behavior on your WordPress site.

## Features

- Automatic affiliate deal popups triggered by user activity
- Easily manage multiple affiliate deals via JSON input
- Displays customizable titles, descriptions, and call-to-action links
- Lightweight, no bloat frontend scripts
- Admin panel for managing affiliate deals
- Freemium-ready architecture for future premium extensions

## Installation

1. Upload `smart-affiliate-deal-booster.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Affiliate Deal Booster** to configure your affiliate deals.

## Setup

- In the settings page, enter your affiliate deals as a JSON array, for example:


[
  {
    "title": "Save 20% on Product X!",
    "url": "https://example.com/deal1",
    "description": "Limited time 20% discount for our visitors."
  },
  {
    "title": "Get 15% Off Service Y",
    "url": "https://example.com/deal2",
    "description": "Use our exclusive affiliate link to save!"
  }
]


- Save changes. Deals will be served randomly (future premium versions will include behavior targeting).

## Usage

- The plugin automatically shows a popup with a deal about 15 seconds after a visitor lands on your site.
- Visitors can close the popup at any time.
- You can customize appearance and behavior by editing CSS or extending the plugin.

## FAQ

**Q: Can I integrate this with other affiliate programs?**

A: Currently, you can add any affiliate URLs manually in JSON. Premium versions will support multi-network integration.

**Q: Can I control popup timing or frequency?**

A: Basic timing is fixed but can be customized in the included JS. Premium versions will allow more control.

## Changelog

### 1.0
- Initial release with popup affiliate deal display
- Admin UI for deal management
- AJAX-based deal retrieval

---

For support and feature requests, visit the plugin support page.
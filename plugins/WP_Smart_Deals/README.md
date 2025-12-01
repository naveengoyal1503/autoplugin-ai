# WP Smart Deals

WP Smart Deals is a WordPress plugin that helps site owners automatically aggregate and display coupons, deals, and discount codes tailored to their niche. This plugin empowers affiliate marketers and deal site owners to monetize their site by providing visitors with timely, relevant savings opportunities.

---

## Features

- Fetches deals automatically from a configurable external JSON deals API endpoint.
- Caches deals for performance and reduces repeated API calls.
- Displays deals using an easy shortcode `[wpsmartdeals]` anywhere in your posts or pages.
- Admin settings page to specify your deal source API and view cached deals.
- Responsive and minimal styling included for clean deal display.

## Installation

1. Upload the `wp-smart-deals.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the 'WP Smart Deals' menu in your WordPress dashboard.
4. Enter your deals API URL or use the default sample API endpoint.
5. Save changes. The plugin will fetch and cache deals automatically every hour.

## Setup

- The plugin requires an API endpoint that returns a JSON array of deal objects.
- Each deal in the API must have at least `title`, `url`, and `discount` fields.
- Optionally, include an `expiry` field to show deal expiration.

Example deal JSON structure:

[
  {
    "title": "50% off on Widgets",
    "url": "https://example.com/get-deal",
    "discount": "50%",
    "expiry": "2025-12-31"
  },
  {
    "title": "Buy one get one free Shoes",
    "url": "https://example.com/shoes-deal",
    "discount": "BOGO"
  }
]


## Usage

- Add the shortcode `[wpsmartdeals]` inside any post, page, or widget where you want to display the current deals.
- Deals will be refreshed automatically every hour via scheduled background task.
- Customize the CSS as needed by overriding styles in your theme.

**Monetization:**
- Offer sponsored deal submissions and promotions as a premium addon.
- Upgrade site owners to premium for advanced deal filtering, styling options, and priority support.

---

Developed by YourName
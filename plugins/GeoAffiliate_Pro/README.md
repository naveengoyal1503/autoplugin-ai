# GeoAffiliate Pro

GeoAffiliate Pro is an advanced WordPress plugin that enables affiliate marketers and online businesses to manage and optimize affiliate links using geolocation targeting, scheduling, and automatic insertion. This plugin helps maximize affiliate revenue by showing region-specific affiliate URLs and allowing scheduled promotions.

---

## Features

- Automatic affiliate link replacement based on visitor country
- Geolocation detection using IP address (Cloudflare header or external API fallback)
- Schedule affiliate link activation with start and end dates
- Shortcode `[geoaffiliate text="Link Text"]` for adding affiliate links anywhere
- Minimal click tracking via simple CSV log with IP and country
- Works out-of-the-box with no setup needed for basic usage

## Installation

1. Upload the `geoaffiliate-pro.php` file to your WordPress `wp-content/plugins/` directory.
2. Activate the plugin through the WordPress admin dashboard under Plugins.
3. Use the shortcode `[geoaffiliate text="Buy Now"]` in your posts/pages to insert affiliate links.

## Setup

- Currently, affiliate links and schedules are hardcoded for demonstration.
- In future versions, an admin interface will allow managing links and timeframes.

## Usage

- Place the shortcode `[geoaffiliate text="Special Offer"]` wherever you want an affiliate link to appear.
- The link will automatically redirect visitors based on their country.
- Link records can be viewed in the CSV `click_log.csv` file inside the plugin folder (server file system access required).

## Monetization

- The plugin can be offered with a freemium model: basic features free, and advanced geotargeting, scheduling, and analytics via premium subscription.
- Potential upsell to integration with popular affiliate networks and WooCommerce.

---

Thank you for using GeoAffiliate Pro! Feel free to contact support for feedback or features requests.
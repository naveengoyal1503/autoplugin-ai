# GeoPromoAffiliate

GeoPromoAffiliate is a WordPress plugin designed to help bloggers, affiliate marketers, and WooCommerce store owners increase affiliate revenue by automatically inserting and cloaking affiliate links with geolocation targeting and scheduled promotions.

## Features

- **Geolocation-based affiliate links:** Shows different affiliate links based on the visitor's country.
- **Scheduled promotions:** Links are active only during defined date ranges.
- **Link cloaking:** Redirects affiliate links through a plugin endpoint to hide raw affiliate URLs.
- **Shortcode support:** Use `[geo_affiliate_link id="prod1"]Your link text[/geo_affiliate_link]` to insert links.
- **Lightweight and self-contained:** Single PHP file, no dependencies.

## Installation

1. Upload `geopromoaffiliate.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Setup

Currently, affiliate links, countries, and schedules are hardcoded in the plugin for demonstration. Future versions will include an admin panel for managing links easily.

## Usage

- Place the shortcode `[geo_affiliate_link id="prod1"]Buy Product 1[/geo_affiliate_link]` anywhere in posts or pages.
- The plugin will detect the user's country and direct them to the appropriate affiliate link if the promotional period is active.
- If no country-specific link matches, it falls back to a default global link if configured.

## Example

html
[geo_affiliate_link id="prod1"]Get the special offer for Product 1![/geo_affiliate_link]


This shortcode will render an anchor tag with the appropriate cloaked affiliate URL based on the visitor's geolocation.

---

*Note: This is a basic proof-of-concept plugin designed for revenue optimization via geolocated affiliate marketing. It can be extended with an admin UI, analytics, and more sophisticated targeting.*
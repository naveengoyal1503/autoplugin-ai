# GeoAffiliate Pro

## Description
GeoAffiliate Pro is a powerful WordPress plugin designed to manage affiliate links with geolocation targeting and scheduled promotions. Perfect for bloggers, affiliate marketers, and online store owners seeking to boost affiliate revenue by delivering region-specific offers and automating link visibility.

## Features
- Manage multiple affiliate links in JSON format
- Target links by visitor geolocation (country ISO codes)
- Schedule affiliate link visibility by date range
- Automatic geolocation detection with caching
- Shortcode support `[geoaffiliate_link id="link_id"]` to embed affiliate links
- Link cloaking with nofollow and noopener attributes

## Installation
1. Upload the `geoaffiliate-pro.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the GeoAffiliate Pro menu in the WordPress admin.

## Setup
1. In the GeoAffiliate Pro settings, insert your affiliate links as a JSON array. Each link should include:
   - `id`: unique link identifier
   - `url`: full affiliate URL
   - `country`: two-letter ISO country code (e.g., US, CA) for targeting
   - `active_from`: start date (YYYY-MM-DD) when the link becomes active
   - `active_to`: end date (YYYY-MM-DD) when the link expires

Example:

[
  {
    "id": "amazon_us",
    "url": "https://amazon.com/dp/product",
    "country": "US",
    "active_from": "2025-01-01",
    "active_to": "2025-12-31"
  }
]


## Usage
- Insert the shortcode `[geoaffiliate_link id="amazon_us"]` anywhere in your content where you want the affiliate link to appear.
- The plugin will automatically display the link only for visitors from the specified country and during the active date range.

## Monetization
GeoAffiliate Pro is suitable for offering a free basic version with manual link entry, while premium upgrades could include:
- Advanced analytics dashboard
- Automatic link cloaking and tracking
- Multiple country targeting per link
- Scheduled link replacements and promos

---

Developed by Your Name.
# GeoAffiliate Smart Links

GeoAffiliate Smart Links is a WordPress plugin that automatically inserts, cloaks, and geo-targets affiliate links in your content to maximize earnings by showing region-relevant offers.

## Features

- Automatic affiliate link insertion after the first paragraph of posts
- Cloaking of affiliate links via redirect URLs for better tracking
- Geo-targeting based on visitor IP to show country-specific affiliate offers
- Shortcode `[geoaffiliate_link]` supports dynamic affiliate links in posts and pages
- Admin settings to manage affiliate links as a JSON array

## Installation

1. Upload the `geoaffiliate-smart-links.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > GeoAffiliate Links** to configure your geo-targeted affiliate links.

## Setup

- Enter your affiliate links in the JSON format, each with a `country` (ISO 2-letter code), `url`, and `text`. Example:


[
  {
    "country": "US",
    "url": "https://affiliatesite.com/us-product",
    "text": "Buy US Version"
  },
  {
    "country": "CA",
    "url": "https://affiliatesite.com/ca-product",
    "text": "Buy Canada Version"
  }
]


- Save changes.

## Usage

- Use the shortcode `[geoaffiliate_link text="Check this offer" default_url="https://defaultaffiliate.com"]` inside posts or pages to insert a geo-targeted affiliate link.
- The plugin automatically inserts affiliate links based on visitor location after the first paragraph of singular posts.
- All affiliate links are cloaked via redirect URLs for reliable tracking.

## Monetization Strategy

- The plugin can be offered as a free core with a premium extension selling advanced features such as scheduled link replacements for time-limited deals, expanded geolocation targeting (regions, cities), and statistics dashboard.
- Target affiliate marketers and bloggers who want to leverage geo-targeting to improve affiliate conversions.
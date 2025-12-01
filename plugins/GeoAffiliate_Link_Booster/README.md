# GeoAffiliate Link Booster

GeoAffiliate Link Booster automatically inserts and cloaks your affiliate links within WordPress posts, targeting visitors by their geographic location and allowing scheduled promotions for maximum affiliate income.

## Features

- Automatic detection of visitor country via IP geolocation
- Insert affiliate links only to users from specified countries
- Schedule affiliate links to activate between specific dates
- Cloak affiliate URLs with in-plugin ref parameters
- Easy JSON configuration of affiliate keywords and links

## Installation

1. Upload the `geoaffiliate-link-booster.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > GeoAffiliate Links to configure your affiliate links.

## Setup

- Provide your affiliate links in JSON format in the settings.
- Each link object should have:
  - `keyword`: the word to replace with the affiliate link
  - `url`: the affiliate destination URL
  - `geo`: array of ISO 2-letter country codes where the link should appear
  - Optional `start` and `end` dates (YYYY-MM-DD) to schedule the links

Example:


[
  {
    "keyword": "product",
    "url": "http://aff.example.com/product?ref=123",
    "geo": ["US", "CA"],
    "start": "2025-12-01",
    "end": "2025-12-31"
  }
]


## Usage

- Write your posts using the keywords configured.
- When visitors from allowed countries view your posts, the keywords will be replaced by cloaked affiliate links.
- Links outside the schedule or from other countries will not show.
- Use this plugin to tailor affiliate marketing by geography and timed campaigns.

This plugin helps maximize revenue through intelligent affiliate link placement and targeting.
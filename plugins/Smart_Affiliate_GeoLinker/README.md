# Smart Affiliate GeoLinker

## Description
Smart Affiliate GeoLinker is a powerful WordPress plugin that automatically cloaks, manages, and geo-targets your affiliate links. It helps you boost affiliate conversions by redirecting users to region-specific affiliate offers based on their country.

## Features
- Manage affiliate links easily via JSON input in the admin panel
- Cloak affiliate links with simple URL slugs
- Geo-target visitors to country-specific affiliate URLs for higher conversions
- Automatic 301 redirects for SEO benefits
- Lightweight, single-file plugin with no dependencies
- Basic IP-to-country detection using free API with caching

## Installation
1. Upload the plugin PHP file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the WordPress admin sidebar, click on 'Affiliate GeoLinker'.
4. Enter your affiliate links configuration in JSON format (see Usage below).
5. Save the settings.

## Setup
The affiliate links JSON accepts an array of link objects with the following properties:
- `slug`: unique string identifier used in URL
- `url`: default affiliate URL
- `geo`: (optional) object mapping country codes (ISO 2-letter) to country-specific URLs

Example:

[
  {
    "slug": "product1",
    "url": "https://aff.example.com/product1",
    "geo": {
      "US": "https://us.aff.example.com/product1",
      "FR": "https://fr.aff.example.com/product1"
    }
  }
]


## Usage
Use the URLs in your content like:

`https://yourdomain.com/aff/product1`

Users from the US will be redirected to the US-specific affiliate link, while others will go to the default link.

## Monetization
This plugin can be offered free with advanced geo-targeting analytics, link scheduling, and premium support as subscription upgrades to generate revenue.

## Support
For support or feature requests, please contact the author.

---

*Developed by Perplexity AI*
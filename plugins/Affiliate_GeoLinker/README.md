# Affiliate GeoLinker

Affiliate GeoLinker is a WordPress plugin that automatically inserts and cloaks affiliate links in your content based on the visitor's geolocation, displaying region-specific offers to increase conversion rates and affiliate revenue.

## Features

- Automatic cloaked affiliate link insertion based on predefined keywords
- Geolocation-based targeting to show specific affiliate links by country
- Simple link cloaking through redirect to protect affiliate URLs
- Easy configuration in WordPress admin with affiliate URL, text, and country codes
- Lightweight, no external API needed for basic geolocation
- Freemium-ready for future advanced features (scheduled replacements, analytics)

## Installation

1. Upload the `affiliate-geolinker.php` file to the `/wp-content/plugins/` directory or install via WordPress plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Affiliate GeoLinker** to configure your affiliate links.

## Setup

- In the settings page, enter your affiliate links, each on a new line in the format:

  `http://affiliate-link.com|Link Text|CountryCode1,CountryCode2`

- The `CountryCode` is optional; if omitted, the link will show for all visitors.
- Example:
  
  `https://example.com/product?ref=123|Buy Widget|US,CA`

- Save settings.

## Usage

- Write your posts/pages using the exact 'Link Text' as part of your content where you want the affiliate link inserted.
- The plugin will automatically replace that text with a cloaked affiliate link for visitors from the specified countries.
- Visitors outside listed countries will see the plain text or other matching links without links.

Enjoy improved affiliate marketing conversions with fully automated link management tailored to your visitors' locations!
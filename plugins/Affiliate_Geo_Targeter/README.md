# Affiliate Geo Targeter

## Description
Affiliate Geo Targeter is a WordPress plugin that automatically replaces affiliate links on your website based on the visitor's geographic location. This enables site owners to display region-specific affiliate offers, improving conversion rates and maximizing affiliate commissions.

## Features
- Automatic geolocation-based affiliate link replacement
- Cloaked affiliate links with a simple shortcode
- Support for multiple regions with fallback to a default affiliate link
- Simple, lightweight, and fast

## Installation
1. Upload the `affiliate-geo-targeter.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Insert the shortcode `[geo_affiliate text="Buy Now"]` in posts or pages where you want to display geotargeted affiliate links.

## Setup
By default, the plugin uses a predefined set of affiliate URLs for US, CA, GB, IN, and a default global URL. To customize these URLs, edit the `$affiliate_links` array inside the plugin file.

Example:

php
private $affiliate_links = array(
  'US' => 'https://example.com/affiliate/us?ref=123',
  'CA' => 'https://example.com/affiliate/ca?ref=123',
  'DEFAULT' => 'https://example.com/affiliate/global?ref=123'
);


## Usage
Use the shortcode `[geo_affiliate text="Your Text Here"]` anywhere in your content to display an affiliate link tailored to each visitor's country.

Example:

html
[geo_affiliate text="Shop Now"]


The link will automatically point to the correct affiliate URL based on visitor location using a free geolocation API.

## Monetization
Offer this plugin as a free basic version with limits on the number of regions supported, and sell premium add-ons to:
- Add support for more countries
- Provide detailed click analytics
- Enable scheduling of affiliate links
- Allow automatic link insertion into content

This creates a strong value proposition for affiliate marketers to upgrade and increase their affiliate revenue.

---
*Developed for WordPress affiliate marketers seeking an easy way to optimize commissions based on visitor location.*
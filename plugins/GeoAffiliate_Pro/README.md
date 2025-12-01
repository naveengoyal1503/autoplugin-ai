# GeoAffiliate Pro

GeoAffiliate Pro is a WordPress plugin for affiliate marketers and bloggers who want to boost affiliate revenue by automatically geo-targeting affiliate links and scheduling their activation.

## Features

- Create affiliate links identified by a customizable slug.
- Assign default URLs and multiple geo-targeted URLs by country code.
- Schedule start and end times to activate/deactivate affiliate URLs.
- Automatically redirect visitors to region-specific affiliate links based on their IP.
- Shortcode `[geoaffiliate slug="yourlink"]` outputs the dynamically resolved URL.
- Admin panel to manage all affiliate links with geo and schedule settings.

## Installation

1. Upload the `geoaffiliate-pro.php` file to your WordPress plugins directory or install via the WP admin plugin uploader.
2. Activate the plugin through the Plugins menu in WordPress.
3. You will see a new admin menu "GeoAffiliate Pro" where you can add and manage your affiliate links.

## Setup

1. In the GeoAffiliate Pro admin page, fill out the form to create a new affiliate link:
   - **Slug:** Unique identifier used in shortcode
   - **Default URL:** URL to use if no geo-targeting applies
   - **Geo Targets:** One per line using ISO country codes, e.g., `US=https://amazon.com`
   - **Schedule Start/End:** Optional activation window using `YYYY-MM-DD HH:MM` format
2. Save the link.
3. Repeat to add more links.

## Usage

Place the shortcode anywhere in your posts or pages:


[geoaffiliate slug="mylink"]


This will output the correct affiliate URL based on the visitor's location and active schedule.

For clickable affiliate links that update dynamically, use an anchor tag with a special attribute:

html
<a href="#" data-geoaffiliate="mylink">Buy now</a>


The plugin will replace the href dynamically on page load.

## Monetization Model

GeoAffiliate Pro uses a freemium model. Basic features are free; premium upgrades could add detailed analytics, multi-network integration, branded link shortening, and prioritized support.

---
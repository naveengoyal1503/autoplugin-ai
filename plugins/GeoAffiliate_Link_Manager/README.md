# GeoAffiliate Link Manager

**GeoAffiliate Link Manager** is a WordPress plugin designed to optimize your affiliate marketing by automatically displaying geo-targeted affiliate links with scheduling capabilities to maximize your earnings.

## Features

- Manage multiple affiliate links with names, URLs, and country targeting by 2-letter country codes.
- Schedule affiliate link activation with start and end dates.
- Automatically displays the correct affiliate link based on the visitor's geolocation.
- Simple shortcode to insert geo-targeted links anywhere in posts or pages.
- Remove or add affiliate links easily from the admin panel.

## Installation

1. Upload the `geoaffiliate-link-manager.php` file to your `/wp-content/plugins/` directory or install via the WordPress plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the new admin menu 'GeoAffiliate Links' to add and manage your affiliate links.

## Setup

1. On the settings page, click 'Add New Link' to create affiliate entries.
2. Enter a descriptive Link Name, the target URL, and optionally a 2-letter Country Code (e.g., 'US', 'CA', 'GB'). Leave empty to show globally.
3. Optionally set Start and End dates to schedule when the link should be active.
4. Save changes.

## Usage

- Insert affiliate links in your posts/pages using the shortcode:

  `[gaflm_affiliate_link name="LinkName"]`

- Replace `LinkName` with the exact Link Name you entered.
- Visitors will be shown the URL that matches their country and current date range.

## Example

If you have two affiliate links named 'AmazonDeal' targeting 'US' and 'UK' with different URLs, the shortcode `[gaflm_affiliate_link name="AmazonDeal"]` will display the US or UK URL depending on the visitor location.

## Support

For support, please open a ticket on the plugin support forum or contact the developer.

## Changelog

### 1.0
- Initial release with geo-targeting and scheduling for affiliate links.
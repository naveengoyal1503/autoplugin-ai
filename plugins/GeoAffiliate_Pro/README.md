# GeoAffiliate Pro

## Description
GeoAffiliate Pro automatically inserts geographically targeted affiliate links and coupons on your WordPress site to increase conversions and affiliate revenue by delivering region-specific offers to your visitors.

## Features

- Auto-detect visitor location by IP
- Show affiliate links based on visitor country
- Display custom coupon codes along with links
- Simple shortcode for easy insertion: `[geo_affiliate_link text="Buy Now" coupon="SAVE10"]`
- Lightweight and self-contained single PHP file plugin
- Responsive and styled affiliate link output

## Installation

1. Download the `geoaffiliate-pro.php` file.
2. Upload it to your WordPress site's `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Insert shortcode `[geo_affiliate_link]` into posts, pages, or widgets where you want affiliate links.

## Setup

- To customize affiliate URLs, edit the `$affiliate_links` array in the plugin code.
- You can add new country codes and their corresponding affiliate URLs.
- Use the shortcode attributes `text` and `coupon` to customize the display text and coupon code.

## Usage

Place the shortcode in your content like this:

text
[geo_affiliate_link text="Shop the deal" coupon="DISCOUNT20"]


This will render an affiliate link targeting the visitor's country with a coupon code.

## Support

For support or feature requests, please contact [your-email@example.com].

## License

This plugin is licensed under the GPL2 license.
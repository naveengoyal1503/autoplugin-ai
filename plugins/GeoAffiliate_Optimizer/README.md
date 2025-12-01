# GeoAffiliate Optimizer

GeoAffiliate Optimizer automatically detects your visitors' geolocation and displays customized affiliate offers and coupons tailored to their country, increasing your affiliate conversions and revenue.

## Features

- Geolocation-based automatic affiliate link and coupon display.
- Supports multiple country-specific offers with fallback global offer.
- Simple shortcode `[geo_affiliate_offer]` to insert affiliate offer anywhere.
- Lightweight and self-contained single PHP file.
- Cookie caching for performance.

## Installation

1. Download the `geoaffiliate-optimizer.php` file.
2. Upload it to the `/wp-content/plugins/` directory.
3. Activate the plugin through the WordPress admin panel.
4. Place the shortcode `[geo_affiliate_offer]` in posts, pages, or widgets where you want the offer to appear.

## Setup

- By default, the plugin includes sample affiliate URLs and coupons for the US, UK, Canada, Australia, and a global fallback.
- Advanced users can modify the `$supported_countries` array inside the plugin PHP file to add or change offers.

## Usage

- Insert `[geo_affiliate_offer]` shortcode in your content to show the personalized affiliate offer.
- Visitors from supported countries will see their localized offers automatically.
- Visitors from unsupported countries see the default global offer.

---

Maximize your affiliate marketing revenue with GeoAffiliate Optimizer's targeted offers powered by geolocation technology.
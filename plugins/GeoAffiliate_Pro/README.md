# GeoAffiliate Pro

## Description
GeoAffiliate Pro automatically displays affiliate links targeted by visitor region using geolocation, with scheduling and link cloaking. Maximize your affiliate commissions by showing relevant offers only where and when they apply.

## Features
- Region-based affiliate link display using visitor IP geolocation
- Link cloaking via simple redirecting to mask affiliate URLs
- Schedule affiliate links with start and end dates
- Easy admin interface to add/edit region-linked URLs
- Shortcode `[geoaffiliate]` to insert geo-targeted affiliate links anywhere

## Installation
1. Upload `geoaffiliate-pro.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the GeoAffiliate Pro menu in the admin panel.
4. Add your affiliate links with region codes (ISO country codes), URLs, and optional date ranges.

## Setup
- Enter two-letter country codes (e.g., US, GB, CA) to target specific regions.
- Provide the affiliate URL for each region.
- Optionally set start and end dates to activate links only during promotion periods.

## Usage
- Place the shortcode `[geoaffiliate]` in posts, pages, or widgets where you want the targeted affiliate link to appear.
- Visitors will see the affiliate link relevant to their country if configured.

## Monetization Strategy
Built for affiliate marketers seeking targeted and scheduled promotions with a freemium approach planned for premium features like analytics and WooCommerce integration.

---

**Note:** Basic geolocation uses free external IP API; for high volume, upgrading to a premium IP geolocation service is recommended to ensure reliability and accuracy.
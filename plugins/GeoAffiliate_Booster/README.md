# GeoAffiliate Booster

GeoAffiliate Booster is a WordPress plugin designed to supercharge your affiliate marketing by automatically inserting, cloaking, and targeting affiliate links according to your visitors' geolocation and scheduled promotions. This helps increase conversion rates by serving personalized affiliate offers.

## Features

- Automatic detection and replacement of affiliate keywords with cloaked affiliate links in post/page content.
- Cloaked URLs in the format `/go/your-slug` for cleaner and trackable affiliate links.
- Geolocation targeting: Show specific affiliate links based on visitor country.
- Scheduled promotions: Activate or deactivate affiliate offers on set dates.
- Easy admin UI for managing affiliate links, geolocation rules, and schedules.
- Freemium-friendly architecture (core features in one file, extensible).

## Installation

1. Upload the `geoaffiliate-booster.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > GeoAffiliate Booster** to configure your affiliate links and targeting rules.

## Setup

- **Affiliate Links:** Enter your affiliate links with the format `keyword|affiliate_URL|cloaked_slug` on each line.
- **Geolocation Rules:** Specify rules per line in the format `country_code|cloaked_slug|start_date|end_date` to show links only to visitors from certain countries during specific periods.
- **Scheduled Promotions:** Define promotional periods per cloaked slug in the format `cloaked_slug|start_date|end_date` to enable time-limited offers.

## Usage

- Once configured, the plugin automatically replaces the first occurrence of each affiliate keyword in your content with a cloaked affiliate link tailored to the visitor’s location and schedule.
- Use the cloaked links (e.g., `https://yoursite.com/go/proda`) anywhere for consistent branding and tracking.

## Support

For bugs or feature requests, please open an issue in the repository or contact the developer.

---

*Boost your affiliate revenue with smart, automated link targeting!*
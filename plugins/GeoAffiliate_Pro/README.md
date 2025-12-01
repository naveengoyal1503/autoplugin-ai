# GeoAffiliate Pro

**GeoAffiliate Pro** is an advanced WordPress plugin for affiliate marketers that provides **automatic affiliate link cloaking, geolocation targeting**, and **promotion scheduling**.

---

## Features

- Create custom cloaked affiliate links (e.g., yoursite.com/promo)
- Assign affiliate destination URLs per cloaked link
- Geolocation targeting: redirect visitors based on their country
- Schedule promotions with start and end dates
- Shortcode support to dynamically display cloaked affiliate links
- Easy-to-use admin interface for link management
- Use native WordPress hooks and standards

---

## Installation

1. Upload the `geoaffiliate-pro.php` file to your WordPress plugins directory `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the plugin settings from the admin sidebar menu "GeoAffiliate Pro".

---

## Setup

1. Add affiliate links through the settings page.
2. Provide a **unique base URL slug** for cloaking (e.g., `promo`).
3. Enter the full affiliate URL where visitors should be redirected.
4. Optionally, enter country codes (ISO 3166-1 alpha-2) to restrict redirection geographically.
5. Optionally, set start and end dates to schedule active promotions.
6. Save your settings.

---

## Usage

- Insert cloaked links anywhere via shortcode:

  
  [geoaffiliate_link name="promo"]
  

- This generates a clickable link with the cloaked URL (e.g., yoursite.com/promo).
- Visitors are redirected automatically to the correct affiliate URL based on their country and promotion schedule.

---

## Monetization

GeoAffiliate Pro uses a **freemium model**:

- Base plugin is free with core features.
- Premium version (planned for future) adds advanced analytics, multiple geotarget rules, and scheduled bulk link management.

---

## Support

For support and feature requests, please visit the plugin support forum or contact the author directly.

Thank you for choosing GeoAffiliate Pro to maximize your affiliate marketing revenue!
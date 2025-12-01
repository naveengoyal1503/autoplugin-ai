# Affiliate Link Booster

## Description
Affiliate Link Booster improves your affiliate marketing by automatically cloaking affiliate links, categorizing them, applying geotargeting, and scheduling link activations. Increase commissions by showing the right offers to the right audience at the right time.

## Features
- Automatic cloaking of affiliate links through internal redirect to hide ugly URLs
- Keyword-based link replacement in post content
- Geolocation targeting to show links only to visitors from specified countries
- Scheduling support with start and end dates for time-limited campaigns
- JSON-based link management directly from WordPress admin

## Installation
1. Upload `affiliate-link-booster.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the "Affiliate Link Booster" menu to configure your links

## Setup
1. Prepare your affiliate links in JSON format. Example entry:

{
  "keyword": "BlueWidget",
  "url": "https://affiliate.example.com/bluewidget",
  "category": "widgets",
  "countries": ["US", "CA"],
  "start_date": "2025-12-01",
  "end_date": "2025-12-31"
}

2. Enter an array of such link objects in the plugin settings JSON textarea.
3. Save changes.
4. Add posts containing the keywords (e.g., "BlueWidget") to automatically link them to your affiliate URLs.

## Usage
- The plugin automatically replaces the first occurrence of each keyword per post with a cloaked affiliate link.
- Links open in a new tab with `nofollow` and `noopener` attributes to protect SEO and security.
- Visitor geoIP country detection uses Cloudflare header `HTTP_CF_IPCOUNTRY` if available.
- Access link click logging and advanced features planned for premium upgrades.

## Monetization Model
The core plugin is free for basic cloaking and keyword replacement. Advanced geotargeting, link scheduling, and detailed analytics will be available in a premium subscription.

## Support
For issues or feature requests, please open a ticket in the plugin support forum.
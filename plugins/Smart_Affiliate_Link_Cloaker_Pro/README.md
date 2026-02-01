# Smart Affiliate Link Cloaker Pro

A freemium WordPress plugin to cloak affiliate links, track clicks with detailed analytics, and optimize conversions.

## Features

### Free Version
- **Link Cloaking**: Use `[afflink url="https://affiliate.com" text="Buy Now" id="link1"]` shortcode to create pretty, trackable links.
- **Click Tracking**: Logs IP, user agent, timestamp in WordPress database.
- **Easy Setup**: No configuration needed; works out-of-the-box.

### Pro Version ($9/month)
- **A/B Testing**: Test two affiliate links and auto-redirect to the better performer.
- **Advanced Analytics**: Dashboard with charts, conversion estimates, geolocation.
- **Unlimited Links**: No limits on tracked links or clicks.
- **Export Data**: CSV exports for external tools.
- **Priority Support**: Email support within 24 hours.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin: **Plugins > Add New > Upload Plugin**.
3. Activate the plugin.
4. Start using shortcodes immediately.

## Setup

No setup required for basic use. Add this to any post/page:


[afflink url="https://your-affiliate-link.com/product" text="Get 50% Off" id="promo1"]


- `url`: Your raw affiliate URL.
- `text`: Display text.
- `id`: Unique identifier for tracking (optional).

View stats in a future dashboard (Pro) or query the `wp_sac_clicks` table.

## Usage

1. Insert shortcode in posts, pages, or widgets.
2. Links redirect via AJAX tracking (no page flicker).
3. Free version nags for upgrade; dismissible.

## Upgrade to Pro

Visit [example.com/pro](https://example.com/pro) for subscription. Enter license key in **Settings > Affiliate Cloaker**.

## Support

- Free: WordPress.org forums.
- Pro: Submit ticket at example.com/support.

## Changelog

**1.0.0**
- Initial release with cloaking and tracking.
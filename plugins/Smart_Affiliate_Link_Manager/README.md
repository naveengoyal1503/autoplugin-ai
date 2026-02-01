# Smart Affiliate Link Manager

## Features

- **Automatic Link Cloaking**: Detects and cloaks affiliate links in posts and pages for better UX and tracking.
- **Click Tracking**: Logs clicks with IP, user agent, and timestamp (stored in custom DB table).
- **Shortcode Support**: Use `[salmlink url="https://affiliate-link.com"]` for manual links.
- **Admin Dashboard**: Configure affiliate keywords (e.g., amazon, clickbank) and view basic stats.
- **Free Forever**: Core features are free.

**Premium Add-ons (coming soon)**:
- Advanced analytics dashboard with charts.
- A/B link testing.
- Integrations: WooCommerce, Amazon Associates, Pretty Links.
- Unlimited links and custom domains.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Upload and activate.
4. Configure in **Settings > Affiliate Links**.

## Setup

1. Go to **Settings > Affiliate Links**.
2. Add comma-separated keywords for auto-detection (e.g., `amazon,clickbank,commission`).
3. Save changes.
4. Add affiliate links to posts; they auto-cloak on frontend.

## Usage

- **Automatic**: Write affiliate URLs in content; they convert to trackable cloaked links.
- **Manual**: `[salmlink url="https://example.com/aff?ref=123"]`.

Clicks redirect after logging. View raw data in DB or upgrade for dashboard.

## Support

Report issues on GitHub or contact support@example.com.

## Upgrade to Pro

Unlock premium features for $49/year at [example.com/pro](https://example.com/pro).
# ContentMoat Pro

AI-powered content monetization analytics and tracking plugin for WordPress.

## Features

- **Link Tracking**: Track clicks on affiliate links, sponsored content, and ad placements
- **Revenue Dashboard**: Visualize total clicks, conversions, and revenue in real-time
- **Content Performance Analysis**: Identify your top-performing posts and monetization opportunities
- **Multi-Link Type Support**: Track affiliate links, sponsored posts, ad networks, and more
- **Conversion Monitoring**: Monitor conversion rates alongside click-through rates
- **Advanced Reporting**: Generate detailed reports on content revenue performance
- **Shortcode Integration**: Embed monetization dashboards anywhere on your site
- **AJAX-Powered**: Real-time data updates without page reloads

## Installation

1. Download the ContentMoat Pro plugin files
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin dashboard
4. Navigate to ContentMoat Pro menu to configure settings

## Setup

1. Go to **ContentMoat Pro > Settings** in your WordPress dashboard
2. Enter your API key if using premium features
3. Enable tracking for your content
4. Start adding tracked links to your content

## Usage

### Dashboard Access

Visit **ContentMoat Pro > Dashboard** to view:
- Total clicks across all tracked content
- Total revenue generated
- Number of active tracked links
- Top performing posts

### Shortcode

Add this shortcode to any page or post to display the monetization dashboard:


[contentmoat_dashboard]


### Tracking Links

Link tracking occurs automatically when:
- Visitors click affiliate links
- Sponsored content is accessed
- Ad impressions are recorded

## Database

The plugin creates a `wp_contentmoat_tracking` table to store:
- Post IDs
- Link URLs
- Click counts
- Conversion data
- Revenue information
- Link types
- Timestamps

## Settings

**API Key**: For premium analytics features and integrations

**Enable Tracking**: Master toggle for all tracking functionality

## Monetization Models Supported

- Affiliate Marketing
- Sponsored Content
- Display Ads
- Product Recommendations
- Content Paywalls

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+

## License

GPL v2 or later

## Support

For issues and feature requests, contact support@contentmoat.local
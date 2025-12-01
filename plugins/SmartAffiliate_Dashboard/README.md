# SmartAffiliate Dashboard

A comprehensive affiliate marketing management and optimization plugin for WordPress that helps you track, manage, and monetize your affiliate links with advanced analytics and insights.

## Features

- **Centralized Link Management**: Easily create, edit, and organize all your affiliate links in one dashboard
- **Click Tracking**: Automatic tracking of clicks on every affiliate link
- **Advanced Analytics**: Real-time data on clicks, conversions, and conversion rates
- **Link Cloaking**: Hide long affiliate URLs behind clean, branded short codes
- **Category Organization**: Organize links by category for better management
- **Commission Tracking**: Track commission rates for each affiliate program
- **Shortcode Integration**: Embed affiliate links anywhere on your site using `[smartaffiliate_link code="short_code"]`
- **REST API**: Full REST API for programmatic access
- **Freemium Model**: Basic features free, premium features available through subscription

## Installation

1. Download the plugin files
2. Upload the `smartaffiliate-dashboard` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress Plugins menu
4. Navigate to the SmartAffiliate menu in your dashboard

## Setup

1. **Access the Dashboard**: Go to SmartAffiliate → Dashboard in your WordPress admin
2. **Create Your First Link**: Click "Add New Link" and enter:
   - Link Name: A descriptive name for your affiliate link
   - Original URL: The full affiliate URL to track
   - Category: Organize links by topic
   - Commission Rate: The percentage you earn
3. **Get Your Short Code**: Each link receives an auto-generated short code
4. **Share Your Links**: Use the short code in your content or copy the tracking URL

## Usage

### Adding Links via Dashboard

1. Go to SmartAffiliate → Manage Links
2. Click "Add New Link"
3. Fill in the details and save
4. Use the generated short code in your content

### Using Shortcodes

Embed affiliate links in your posts:


[smartaffiliate_link code="your_short_code"]Click Here[/smartaffiliate_link]


### Tracking Performance

1. Navigate to SmartAffiliate → Analytics
2. View real-time metrics:
   - Total clicks on your links
   - Conversion data
   - Top performing links
   - Conversion rates

### REST API Endpoints

- `GET /wp-json/smartaffiliate/v1/links` - Get all links
- `POST /wp-json/smartaffiliate/v1/links` - Create new link
- `PUT /wp-json/smartaffiliate/v1/links/{id}` - Update link
- `DELETE /wp-json/smartaffiliate/v1/links/{id}` - Delete link
- `GET /wp-json/smartaffiliate/v1/analytics` - Get analytics data
- `POST /wp-json/smartaffiliate/v1/track-click` - Track a click

## Monetization Strategy

SmartAffiliate uses a **freemium model**:

**Free Version Includes**:
- Unlimited affiliate link creation
- Basic click tracking
- Simple analytics dashboard
- Community support

**Premium Version ($29/month)** Includes**:
- Advanced analytics with custom date ranges
- AI-powered optimization suggestions
- Bulk link management
- API rate limit removal
- Priority email support
- Link performance reports
- Integration with major affiliate networks

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Support

For support, visit our website or contact support@smartaffiliate.local

## License

GPL v2 or later
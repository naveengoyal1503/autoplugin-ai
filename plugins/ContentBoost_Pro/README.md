# ContentBoost Pro

AI-powered content optimization and repurposing plugin for WordPress monetization.

## Features

- **Content Analysis Dashboard**: Track total posts analyzed, content repurposed, engagement metrics, and estimated revenue
- **Multi-Format Repurposing**: Convert blog posts into video outlines, social media posts, infographic briefs, and podcast scripts
- **Analytics Tracking**: Monitor engagement rates and performance by content format
- **Monetization Recommendations**: Get AI-powered suggestions based on your content performance
- **REST API**: Full REST API support for integrations
- **Shortcode Support**: Display content stats anywhere on your site with `[contentboost_stats]`

## Installation

1. Upload the `contentboost-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Navigate to ContentBoost Pro menu to configure settings

## Setup

1. Go to **ContentBoost Pro > Settings** in the WordPress admin
2. Configure your preferred monetization options (affiliate links, memberships, etc.)
3. Connect your email marketing platform (Mailchimp, ConvertKit, etc.)
4. Set up your content repurposing preferences

## Usage

### Dashboard
Access the main dashboard at **ContentBoost Pro > Dashboard** to view:
- Total posts analyzed
- Content repurposing statistics
- Average engagement lift percentage
- Estimated revenue from monetization

### Repurpose Content
1. Go to **ContentBoost Pro > Repurpose Content**
2. Select a post from your library
3. Choose repurposing formats (video outline, social posts, infographics, podcasts)
4. Click "Repurpose Content" to generate variations

### Analytics
Track performance metrics at **ContentBoost Pro > Analytics** to identify your highest-performing content formats and optimize your monetization strategy.

### Shortcode
Display content statistics on any page or post:

[contentboost_stats]


## Monetization Models

ContentBoost Pro supports multiple revenue streams:

- **Affiliate Marketing**: Track affiliate link performance by content format
- **Sponsored Content**: Identify sponsored content opportunities based on engagement
- **Memberships**: Repurpose premium content for membership tiers
- **Newsletter Subscriptions**: Convert top-performing content into exclusive newsletter tiers
- **Digital Products**: Package repurposed content as downloadable resources

## REST API Endpoints

- `GET /wp-json/contentboost/v1/stats` - Retrieve dashboard statistics (requires admin access)

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+

## License

GPL v2 or later
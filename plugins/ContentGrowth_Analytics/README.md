# ContentGrowth Analytics

AI-powered content performance tracking and monetization optimization plugin for WordPress.

## Features

- **Post Analytics Dashboard**: Real-time metrics for all published posts
- **Engagement Scoring**: Automatic calculation based on content structure, headings, links, and images
- **Monetization Potential Assessment**: Categorizes posts as High, Medium, or Low monetization potential
- **Strategy Recommendations**: Suggests optimal monetization approaches (Affiliate Marketing, Sponsored Content, Display Ads, Membership, Product Sales)
- **Reading Time Calculator**: Automatically calculates estimated reading time
- **Meta Box Integration**: View analytics directly on the post editor
- **Dashboard Widget**: Quick summary on WordPress dashboard
- **REST API**: Access analytics programmatically via REST endpoints
- **Database Optimization**: Efficient storage with post-level tracking

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/contentgrowth-analytics/`
3. Activate the plugin through WordPress Admin Panel
4. Navigate to **CG Analytics** in the admin menu

## Setup

No configuration required! The plugin works out of the box:

- Automatically analyzes posts when saved or published
- Creates database tables on activation
- Displays insights in post editor and admin dashboard

## Usage

### View Dashboard
1. Go to **CG Analytics** in admin menu
2. View top-performing posts ranked by engagement
3. See monetization potential and recommended strategies

### Edit Posts
1. Open any post in editor
2. Scroll to **ContentGrowth Analytics** meta box
3. View real-time scores and recommendations
4. Optimize content based on suggestions

### REST API

Get analytics for a specific post:


GET /wp-json/cga/v1/analytics/123


Response includes: word count, reading time, engagement score, monetization potential, and recommended strategy.

## Monetization Strategy

**Freemium Model**:
- Free version: Core analytics and recommendations
- Premium upgrade: Advanced AI insights, competitor analysis, and automated content optimization
- Affiliate partnerships: Commission on recommended WordPress tools and plugins
- Sponsored insights: Brands pay for featured monetization recommendations

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+

## License

GPL v2 or later
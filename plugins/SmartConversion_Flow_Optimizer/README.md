# SmartConversion Flow Optimizer

AI-powered conversion rate optimization plugin that tracks user behavior and provides actionable insights to increase your WordPress site's conversion rates.

## Features

### Free Version
- Basic event tracking and analytics
- Dashboard with conversion metrics
- Simple page-level tracking
- Up to 10,000 tracked events per month
- Email support

### Premium Version ($29/month)
- Advanced AI-powered recommendations
- Detailed heat map visualizations
- A/B testing capabilities
- Unlimited event tracking
- Priority support
- Custom conversion goals
- Real-time notifications

### Pro Version ($49/month)
- All Premium features
- Funnel analysis and optimization
- Customer journey mapping
- Predictive conversion modeling
- Done-for-you optimization consulting (1 hour/month)
- API access
- Custom integrations

## Installation

1. Upload the plugin files to `/wp-content/plugins/smartconversion-optimizer/`
2. Activate the plugin through the WordPress Admin Dashboard
3. Navigate to **SmartConversion** in the main menu
4. Enable tracking and configure your settings
5. Add the `[sco_heatmap]` shortcode to any page to display heat maps

## Setup

1. Go to **SmartConversion > License**
2. Enter your license key (optional for free tier)
3. Click **Save Settings**
4. Enable tracking in the main settings
5. Start tracking conversions on your key pages

## Usage

### Basic Tracking

The plugin automatically tracks:
- Page views
- Click events
- Form submissions
- Scroll depth
- Time on page
- Conversion events

### Display Heat Maps

Add the shortcode to any page:

[sco_heatmap]


### Track Custom Conversions

The plugin sends AJAX requests to track events. Custom events can be triggered using:
javascript
wp.ajax.post('sco_track_event', {
  page_id: 123,
  event_type: 'custom_conversion',
  x: 100,
  y: 200,
  conversion: 50.00
});


### View Reports

Navigate to **SmartConversion > Reports** to view:
- Conversion trends over time
- Top performing pages
- User behavior patterns
- Revenue impact analysis

## Monetization Model

**SmartConversion Flow Optimizer** uses a hybrid monetization approach:

- **Freemium Tier**: Free version with basic tracking attracts users and builds community
- **Monthly Subscriptions**: Premium ($29/mo) and Pro ($49/mo) tiers provide recurring revenue
- **Annual Licenses**: 20% discount for annual prepayment ($348/year for Premium)
- **Add-on Services**: Professional optimization services at $199/month
- **Upselling**: Free users naturally upgrade when they see value from basic analytics

This model aligns with industry best practices—subscription-based plugins show 65% higher customer retention than one-time purchases.

## Support

For support, visit our website or contact support@smartconversionoptimizer.com

## License

GPL v2 or later
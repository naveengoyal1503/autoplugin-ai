# ContentAI Optimizer Pro

AI-powered WordPress plugin for content optimization, SEO analysis, readability scoring, and engagement metrics.

## Features

- **Comprehensive Content Analysis**: Analyzes your posts for SEO, readability, and engagement
- **SEO Scoring**: Evaluates heading structure, keyword usage, internal linking, and image optimization
- **Readability Metrics**: Calculates Flesch-Kincaid grade level and provides readability recommendations
- **Engagement Scoring**: Measures multimedia usage, formatting, and interactive elements
- **Smart Suggestions**: AI-generated recommendations to improve content performance
- **Freemium Model**: 5 free analyses per month with premium upgrade for unlimited access
- **Usage Tracking**: Monitor your monthly analysis quota and subscription status
- **Post Meta Integration**: Quick access analyzer directly from post editor
- **Dashboard**: Comprehensive dashboard showing subscription status and analysis history

## Installation

1. Download the plugin files to your WordPress installation: `wp-content/plugins/contentai-optimizer/`
2. Alternatively, upload the plugin through WordPress admin: **Plugins > Add New > Upload Plugin**
3. Activate the plugin through the **Plugins** menu in WordPress
4. Navigate to **ContentAI > Dashboard** to begin using the plugin

## Setup

### Initial Configuration

1. Go to **ContentAI > Settings** to configure plugin options
2. Create your account to track subscription and usage data
3. First 5 analyses per month are free for all users
4. Upgrade to Premium ($9.99/month) for unlimited analysis and advanced features

### Database Tables

The plugin automatically creates two tables:

- `wp_contentai_analysis`: Stores analysis results for each post
- `wp_contentai_subscriptions`: Manages user subscription plans and monthly limits

## Usage

### Analyzing Post Content

1. **Edit any post** and scroll to the "ContentAI Optimizer" metabox
2. **Click "Analyze Content"** to run comprehensive analysis
3. **View results** showing:
   - SEO Score (0-100)
   - Readability Score (0-100)
   - Engagement Score (0-100)
   - Overall Score (average of all three)
   - Detailed metrics (word count, headings, links, images)
   - Specific improvement suggestions

### Dashboard Access

- Navigate to **ContentAI > Dashboard** to view:
  - Current subscription status and plan
  - Monthly analysis usage and remaining quota
  - Recent content analyses
  - Option to upgrade to Premium

### Content Metrics

The plugin evaluates:

- **Word Count & Reading Time**: Optimal post length for your audience
- **Heading Structure**: H1/H2/H6 distribution for SEO
- **Formatting Elements**: Lists, blockquotes, bold/italic text
- **Media Usage**: Number and placement of images
- **Link Density**: Internal and external link ratios
- **Sentence Length**: Average words per sentence for readability

## Monetization

### Freemium Pricing

- **Free Plan**: 5 analyses per month, basic metrics
- **Premium Plan**: $9.99/month for unlimited analysis, advanced analytics, priority support

### Revenue Streams

- Monthly subscription fees from premium users
- Affiliate partnerships with AI content tools (Jasper, Copy.ai)
- Affiliate partnerships with SEO plugins and services
- Potential enterprise licensing for agencies

## Support & Troubleshooting

- **No analyses working**: Verify database tables were created during activation
- **Limit reached**: Upgrade to Premium or wait for monthly reset
- **Scores seem off**: Ensure post contains sufficient content (minimum 100 words recommended)

## Development

The plugin includes hooks for developers:

- `contentai_analysis_complete`: Fired after analysis completes
- `contentai_score_calculated`: Fired when scores are calculated
- `contentai_user_upgraded`: Fired when user upgrades subscription

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- jQuery

## License

GPL v2 or later
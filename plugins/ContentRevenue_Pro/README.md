# ContentRevenue Pro

## Overview

ContentRevenue Pro is a comprehensive WordPress monetization plugin that empowers bloggers and content creators to diversify their income streams. It combines affiliate link tracking, sponsored content management, and membership gatekeeping with built-in analytics to maximize revenue from your WordPress site.

## Features

### Affiliate Link Management
- Track and shorten affiliate links with custom slugs
- Monitor commission rates per program
- Track click-through rates and user interactions
- Redirect tracking for accurate performance metrics
- Support for multiple affiliate programs

### Sponsored Content Management
- Add sponsored badges to posts and pages
- Manage sponsor partnerships and branding
- Track sponsored content performance
- Maintain content authenticity with clear sponsor attribution

### Content Gating
- Gate content behind membership levels
- Create multiple membership tiers
- Protect premium content from non-members
- Flexible content restriction options

### Analytics Dashboard
- Real-time click tracking
- Performance metrics for affiliate links
- Daily click statistics
- Revenue potential calculations
- Interactive charts and reports

### REST API
- Create affiliate links programmatically
- Track clicks via REST endpoints
- Integrate with external tools and services

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/contentrevenue-pro/`
3. Activate the plugin through the WordPress admin panel
4. Navigate to ContentRevenue Pro menu to begin setup

## Setup Guide

### First Steps
1. Go to **ContentRevenue Pro > Dashboard** to see your monetization overview
2. Visit **Affiliate Links** to start creating tracked affiliate links
3. Set up **Gated Content** to protect premium posts
4. Review analytics in the **Analytics** section

### Creating Affiliate Links
1. Click "Add New Link" in the Affiliate Links section
2. Enter your affiliate program name
3. Paste your affiliate URL
4. Set your expected commission rate
5. The plugin generates a short slug for easy tracking

### Gating Content
1. Go to **Gated Content** manager
2. Select posts or pages to protect
3. Choose membership level requirements
4. Logged-out users see login prompt; members see full content

### Adding Sponsored Badges
Use the shortcode in your posts:

[crp_sponsored_badge sponsor="Brand Name" url="https://brand-url.com"]


### Inserting Affiliate Links
Use this shortcode with your link ID:

[crp_affiliate_link id="your_link_id" text="Click here" class="custom-class"]


## Monetization Model

ContentRevenue Pro operates on a freemium model:

- **Free Tier**: Basic affiliate tracking, up to 10 links, limited analytics
- **Premium ($9.99/month)**: Unlimited affiliate links, advanced analytics, click patterns, conversion tracking, priority support

## Usage Examples

### Example 1: Product Recommendations
Create an affiliate link to an Amazon product and use the shortcode:

[crp_affiliate_link id="crp_amazon_12abc3" text="Check Price on Amazon"]


### Example 2: Gated Tutorial
Protect advanced content:

[crp_gated_content level="premium"]
Your premium content and tutorial here
[/crp_gated_content]


### Example 3: Sponsored Post
Add sponsorship attribution:

[crp_sponsored_badge sponsor="Tech Company Pro" url="https://techcompany.com"]


## Dashboard Statistics

The main dashboard displays:
- Total number of active affiliate links
- Lifetime click statistics
- Recent affiliate program performance
- Revenue potential estimates

## Analytics Features

- **Click Tracking**: See which links drive the most traffic
- **Date Range Reports**: Analyze performance over time
- **Program Comparison**: Compare different affiliate programs
- **User Insights**: Track anonymous and registered user behavior
- **Conversion Estimates**: Estimate potential earnings

## Support & Documentation

For detailed help with specific monetization strategies, visit our knowledge base or contact support. The plugin integrates with popular affiliate networks including Amazon Associates, Shareasale, and CJ Affiliate.

## Shortcode Reference

| Shortcode | Attributes | Purpose |
|-----------|-----------|----------|
| `[crp_affiliate_link]` | id, text, class | Display trackable affiliate link |
| `[crp_gated_content]` | level | Gate content behind membership |
| `[crp_sponsored_badge]` | sponsor, url | Add sponsor attribution badge |

## Best Practices

1. **Diversify Revenue**: Combine affiliate marketing with sponsored content and memberships
2. **Quality Content**: Gate only premium, high-value content to maintain user trust
3. **Transparency**: Always disclose sponsored content and affiliate relationships
4. **Track Performance**: Regularly review analytics to optimize which programs to promote
5. **User Experience**: Don't gate too much content; balance monetization with reader experience

## Compatibility

- WordPress 5.0 and above
- PHP 7.4 and above
- Works with all WordPress themes
- Compatible with Gutenberg editor

## License

GPL v2 or later
# SmartAffiliate Pro

An intelligent affiliate link management and optimization plugin for WordPress that helps bloggers and content creators maximize their affiliate marketing revenue.

## Features

- **Easy Link Management**: Add, edit, and organize affiliate links with keywords
- **Auto-Injection**: Automatically convert keywords to affiliate links throughout your content
- **Shortcode Support**: Use `[affiliate_link keyword="your_keyword"]` to insert links anywhere
- **Performance Tracking**: Monitor clicks and conversions for each affiliate link
- **Commission Tracking**: Record commission rates for each affiliate program
- **Analytics Dashboard**: View top-performing affiliate links and conversion rates
- **Freemium Model**: Basic features free with premium analytics and bulk management
- **Nonce Security**: Built-in security for all admin operations

## Installation

1. Download the SmartAffiliate Pro plugin
2. Upload the plugin folder to `/wp-content/plugins/` directory
3. Activate the plugin from the WordPress admin panel
4. Navigate to SmartAffiliate in the admin menu to start managing links

## Setup & Configuration

1. Go to **SmartAffiliate > Settings**
2. Enable "Auto-inject Affiliate Links" if you want automatic link conversion
3. Go to **SmartAffiliate > Links** to add your first affiliate link
4. Enter the keyword, affiliate URL, and commission rate
5. Click "Add Affiliate Link"

## Usage

### Method 1: Shortcode

In any post or page, use:


[affiliate_link keyword="your_keyword"]


Replace `your_keyword` with the keyword you registered in the plugin.

### Method 2: Auto-Injection

With auto-injection enabled, any mention of your registered keywords will automatically become affiliate links.

### Method 3: Manual Links

You can also manually edit posts and add affiliate links directly using the standard WordPress link editor.

## Analytics

Track your affiliate performance:

1. Go to **SmartAffiliate > Analytics**
2. View your top 10 performing links sorted by clicks
3. Check conversion rates for each link
4. Identify which keywords drive the most sales

## Monetization

**SmartAffiliate Pro** offers multiple revenue streams:

- **Freemium Model**: Basic link management free, premium features at $9.99/month
- **Premium Features**: Advanced analytics, bulk link import, AI recommendations
- **Affiliate Program**: Earn 30% commission for each referral
- **White-Label**: Rebrand and resell to other WordPress users

## Database

The plugin creates a table `wp_smartaffiliate_links` storing:

- Affiliate keywords
- URLs and commission rates
- Click and conversion tracking
- Creation timestamps

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Support

For issues or feature requests, visit the plugin support page or documentation.

## License

GPL v2 or later
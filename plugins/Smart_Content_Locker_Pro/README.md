# Smart Content Locker Pro

A powerful WordPress plugin to lock premium content behind paywalls and create tiered membership levels to monetize your WordPress site.

## Features

- **Content Locking**: Lock individual posts and pages with flexible locking options
- **Membership Tiers**: Create multiple membership levels with custom pricing
- **Flexible Lock Types**: Choose between partial locks (show excerpt) or full locks
- **User Authentication**: Integrated login system for members
- **Analytics Dashboard**: Track locked content performance and member engagement
- **Payment Processing**: Stripe integration for secure payment processing
- **Billing Management**: Automated recurring billing with subscription management
- **Member Portal**: Members can manage their subscriptions and view access history
- **Email Notifications**: Automated emails for signups, renewals, and expiration
- **Shortcode Support**: Easy integration with `[scl_login_form]` shortcode
- **Freemium Model**: Basic features free, premium features in pro version

## Installation

1. Download the plugin files and upload to `/wp-content/plugins/smart-content-locker/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Content Locker settings to configure your membership tiers
4. Begin locking your premium content

## Setup

### Creating Membership Tiers

1. Go to **Content Locker > Membership Tiers**
2. Click **Add New Tier**
3. Enter tier name (e.g., "Premium", "Gold")
4. Set the monthly or yearly price
5. Add a description of tier benefits
6. Save the tier

### Configuring Payment Processing

1. Go to **Content Locker > Settings**
2. Add your Stripe API keys
3. Set the currency for transactions
4. Configure email notification templates
5. Save settings

## Usage

### Locking Content

1. When creating or editing a post/page, scroll to the **Lock This Content** meta box
2. Check "Lock this content"
3. Choose your lock type:
   - **Partial**: Shows the excerpt to non-members
   - **Full**: Requires login to see any content
4. Select the required membership tier (or "Any tier" for all members)
5. Publish or update the post

### Adding Login Form to Pages

Use the shortcode `[scl_login_form]` on any page or post to display a login form for members.

### Member Management

1. Go to **Content Locker > Members**
2. View all active members and their subscription status
3. Manually adjust member access or tier levels
4. View payment history and subscription details

## Monetization Options

- **Monthly Subscriptions**: $9.99/month for premium features
- **Annual Billing**: Offer discounts for yearly subscriptions
- **Tiered Pricing**: Multiple membership levels at different price points
- **Custom Plans**: Create custom membership tiers for corporate clients

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- Stripe account (for payment processing)

## Support

For support, visit https://smartcontentlocker.com/support or email support@smartcontentlocker.com

## License

This plugin is licensed under the GPL2 License.
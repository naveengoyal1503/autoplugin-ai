# Smart Content Locker Pro

A powerful WordPress plugin for managing content access, memberships, and recurring subscriptions. Monetize your WordPress site by creating tiered membership levels and locking premium content.

## Features

- **Flexible Content Locking**: Lock posts and pages behind membership tiers (Basic, Premium, Elite)
- **Membership Management**: Create and manage multiple membership levels with custom pricing
- **Subscription Tracking**: Automatic subscription lifecycle management with renewal dates
- **Payment Gateway Integration**: Support for Stripe and PayPal integration
- **Content Preview**: Show preview text before users see the lock message
- **User Management**: View active subscribers and manage subscription status
- **Analytics Dashboard**: Real-time tracking of active subscribers and monthly revenue
- **Shortcode Support**: Use `[scl_locked_content level="premium"]` to lock content in posts
- **Meta Box Interface**: Easy-to-use content locking interface in post editor

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/smart-content-locker/`
3. Activate the plugin through WordPress admin panel
4. Navigate to Content Locker > Settings to configure payment gateways

## Setup

### Step 1: Configure Payment Methods

1. Go to **Content Locker > Settings**
2. Enter your Stripe API Key for credit card payments
3. Enter your PayPal email for PayPal integration
4. Click Save Settings

### Step 2: Create Membership Levels

Membership levels are pre-configured as Basic, Premium, and Elite. Customize pricing in your settings.

### Step 3: Lock Content

1. Edit a post or page
2. Scroll to "Content Locker Settings" meta box
3. Check "Lock this content"
4. Select required membership level
5. Add preview text (optional)
6. Publish

## Usage

### Locking Individual Posts

In the post editor, use the Content Locker Settings box to:
- Enable content locking
- Set required membership level
- Add custom preview text

### Using Shortcodes


[scl_locked_content level="premium"]
This content is only visible to premium members.
[/scl_locked_content]


### Managing Subscriptions

View all active subscriptions in **Content Locker > Subscriptions**. Filter by status, user, or membership level.

### Dashboard Analytics

The main dashboard shows:
- Total active subscribers
- Monthly recurring revenue
- Subscriber growth trends

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.7+

## Monetization Model

SmartContentLocker Pro offers multiple revenue streams:

- **Freemium Version**: Basic content locking features free for all users
- **Premium License ($99/year)**: Advanced features, priority support, and unlimited memberships
- **White Label Solutions**: Custom branding and support

## Support

For issues or questions, visit the plugin support page or contact support@smartcontentlocker.com
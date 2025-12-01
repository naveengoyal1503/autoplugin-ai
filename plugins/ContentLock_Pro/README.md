# ContentLock Pro

A powerful WordPress plugin for monetizing content through flexible gating, membership tiers, and subscription management.

## Features

- **Flexible Content Gating**: Lock any post or page behind membership, email capture, or payment walls
- **Multiple Monetization Models**: Support for memberships, subscriptions, one-time payments, and email capture
- **Payment Gateway Integration**: Built-in support for Stripe and PayPal
- **Membership Tiers**: Create multiple subscription levels with different access permissions
- **Shortcode Support**: Easy implementation with `[contentlock]` shortcode
- **User-Friendly Dashboard**: Comprehensive admin interface for managing locked content
- **Analytics**: Track engagement and revenue from locked content
- **Email Integration**: Capture emails and sync with popular email marketing platforms

## Installation

1. Download the ContentLock Pro plugin
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through WordPress admin panel
4. Navigate to ContentLock Pro settings to configure

## Setup

### Payment Gateway Configuration

1. Go to **ContentLock Pro > Settings**
2. Enter your Stripe API Key (get from https://stripe.com)
3. Enter your PayPal Business ID (get from https://paypal.com)
4. Save settings

### Setting Default Lock Message

In the Settings page, customize the default message shown when users encounter locked content. This message appears before the unlock button.

## Usage

### Lock Entire Posts/Pages

1. Edit any post or page
2. Use the ContentLock Pro metabox
3. Select lock type (Email, Membership, Payment)
4. Configure lock settings
5. Publish

### Use ContentLock Shortcode

Add the shortcode to any post or page:


[contentlock type="email" price="4.99" message="Subscribe to read the full article"]


**Shortcode Parameters:**
- `type`: Lock type (email, subscription, payment)
- `price`: Price for one-time payment
- `message`: Custom message for users

### Create Membership Tiers

1. Navigate to ContentLock Pro Dashboard
2. Click "Membership Tiers"
3. Create tier with name, price, and permissions
4. Assign content to tiers

## Monetization Models Supported

- **Email Capture**: Collect emails in exchange for content
- **One-Time Payment**: Charge per article or content piece
- **Monthly Subscriptions**: Recurring revenue from members
- **Tiered Memberships**: Basic, Standard, Premium access levels
- **Sponsored Content**: Partner content from brands

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support, documentation, and updates, visit https://contentlockpro.com

## License

GPL v2 or later
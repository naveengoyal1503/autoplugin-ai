# Smart Content Locker Pro

A powerful WordPress plugin that enables content creators to monetize their website by locking premium content behind email capture, social shares, or payment gates.

## Features

- **Email Capture Locker**: Collect emails from visitors in exchange for content access
- **Social Share Locker**: Require social media shares to unlock premium content
- **Payment Locker**: Accept payments via integrated payment gateways
- **Flexible Lock Rules**: Set different lock types for different content pieces
- **Email Provider Integration**: Connect with Mailchimp, ConvertKit, and other email platforms
- **Analytics Dashboard**: Track unlock rates and conversion metrics
- **Cookie-Based Access**: Remember unlocked content for returning visitors (30 days)
- **Easy Shortcode System**: Use `[locked_content id="unique-id" type="email" preview="Preview text"]` to lock content
- **Mobile Responsive**: Works seamlessly on all devices

## Installation

1. Upload the `smart-content-locker` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress Plugins menu
3. Navigate to Content Locker in the WordPress admin to configure settings

## Setup

1. Go to **Content Locker** in the WordPress admin menu
2. Select your preferred default lock type (Email, Social, or Payment)
3. Connect your email list provider (optional but recommended)
4. Save your settings

## Usage

### Basic Email Locker


[locked_content type="email" preview="Get exclusive access to this premium guide" message="Enter your email to unlock"]
Your premium content goes here
[/locked_content]


### Social Share Locker


[locked_content type="social" preview="This content is worth sharing!" message="Share on social media to unlock"]
Your exclusive content goes here
[/locked_content]


### Payment Locker


[locked_content type="payment" preview="Premium content preview" message="Unlock for just $2.99"]
Your premium paid content
[/locked_content]


## Monetization Models

This plugin supports multiple revenue streams:

- **Email List Building**: Grow your audience and email marketing list
- **Direct Payments**: Accept micropayments for content access
- **Affiliate Commissions**: Partner with email service providers for referrals
- **Subscription Integration**: Combine with membership plugins for recurring revenue

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- jQuery

## Frequently Asked Questions

**Can I track conversions?**
Yes, the plugin includes basic analytics tracking. Premium version offers advanced metrics.

**Is my email data secure?**
All email data is encrypted and complies with GDPR standards.

**Can I customize the unlock form?**
Yes, the premium version includes extensive customization options and CSS controls.

## Support

For support, visit our website or contact our support team.

## License

This plugin is licensed under the GPL v2 or later.
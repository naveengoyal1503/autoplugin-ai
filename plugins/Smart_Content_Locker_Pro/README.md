# Smart Content Locker Pro

A powerful WordPress plugin for monetizing content through paywalls, email gates, and subscription-based access control. Unlock recurring revenue streams with multiple monetization strategies.

## Features

- **Email Gate Locker**: Require email submission to access content
- **Paywall System**: Lock premium content behind a payment wall
- **Tiered Access Levels**: Create multiple subscription tiers with different benefits
- **Advanced Analytics**: Track unlocks, revenue, and user engagement
- **Easy Shortcode Integration**: Use `[content_locker type="email"]` or `[content_locker type="paywall" price="9.99"]`
- **User Management**: Monitor who unlocked what content and when
- **Customizable Settings**: Configure prices, email templates, and messaging
- **AJAX Functionality**: Seamless unlock experience without page reloads

## Installation

1. Download the plugin files
2. Extract to `/wp-content/plugins/smart-content-locker-pro/`
3. Activate the plugin in WordPress admin
4. Navigate to Content Locker > Settings to configure

## Setup

1. Go to WordPress Admin Dashboard
2. Click on "Content Locker" in the left sidebar
3. Navigate to "Settings" tab
4. Configure:
   - Paywall Price (in USD)
   - Email Gate Subject Line
   - Email Body Template
5. Click "Save Settings"

## Usage

### Email Gate Locker

Wrap content with the shortcode:


[content_locker type="email"]
Your premium content here that requires email to view
[/content_locker]


### Paywall Locker

Set custom pricing per content piece:


[content_locker type="paywall" price="7.99"]
Exclusive premium article content
[/content_locker]


### Automatic Post Locking

1. Edit any post and scroll to the Content Locker metabox
2. Select lock type (Paywall or Email Gate)
3. Configure lock settings
4. Update post

## Monetization Models

- **Freemium**: Free basic content with premium unlocks
- **Subscription Tiers**: Monthly or yearly recurring payments
- **Pay-Per-Article**: One-time purchase for individual content
- **Email Capture**: Build mailing list while monetizing

## Dashboard

Track key metrics:
- Total content unlocks
- Revenue by content piece
- User unlock patterns
- Email capture rates

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

## License

GPL v2 or later
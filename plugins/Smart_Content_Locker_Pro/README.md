# Smart Content Locker Pro

Advanced content gating and membership plugin for WordPress that lets you monetize your content with flexible paywalls, email captures, and membership access controls.

## Features

### Core Features (Free Tier)
- **Content Gating**: Lock posts and pages behind simple paywalls
- **Email Capture**: Collect emails before revealing content
- **Social Share Gates**: Unlock content when users share on social media
- **Basic Analytics**: Track locks and unlock attempts
- **Shortcodes**: Use `[sclp_lock]` and `[sclp_locked_content]` to gate content
- **Post Meta Controls**: Simple per-post lock configuration

### Premium Features (Pro Tier - $9.99/month)
- **Advanced Membership Tiers**: Create multiple subscription levels with different content access
- **Payment Integration**: Stripe and PayPal integration for recurring payments
- **Detailed Conversion Analytics**: Track conversion rates by lock type
- **A/B Testing**: Test different unlock messages and designs
- **Custom CSS Editor**: Personalize lock appearance

### Enterprise Features (Elite Tier - $49.99/month)
- **White Label Solution**: Remove Smart Content Locker branding
- **Advanced User Roles**: Custom user access levels
- **API Access**: Integrate with third-party tools
- **Priority Support**: Dedicated support team
- **Advanced Reporting**: Custom reports and exports

## Installation

1. Download the Smart Content Locker Pro plugin files
2. Upload the `smart-content-locker-pro` folder to `/wp-content/plugins/` via FTP or WordPress plugin uploader
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Content Locker > Settings to configure your preferences

## Quick Setup

### Basic Configuration

1. Go to **Content Locker > Settings**
2. Configure your default lock message and unlock button text
3. Choose your preferred payment gateway (if upgrading to Pro)
4. Save your settings

### Locking Content with Shortcodes

Use the shortcode method to gate specific content sections:


[sclp_lock id="lock-1" type="paywall" message="Upgrade to read this exclusive content"]
This content will appear locked
[/sclp_lock]

[sclp_locked_content lock_id="lock-1"]
This content appears after unlock
[/sclp_locked_content]


### Per-Post Configuration

1. Edit any post or page
2. Scroll to the "Content Locker" meta box
3. Enable locking and choose lock type
4. Set your custom message
5. Publish or update the post

## Usage Examples

### Example 1: Email Capture Gate

Require email before showing content:


[sclp_lock id="lead-gen" type="email" message="Enter your email to access this guide"]


### Example 2: Social Share Gate

Unlock content after social share:


[sclp_lock id="social-1" type="social" message="Share this post to unlock exclusive tips"]


### Example 3: Paid Membership Gate

Restrict to paid members only (Pro feature):


[sclp_lock id="premium" type="paywall" message="This content is reserved for members"]


## Supported Lock Types

- **paywall**: Simple button to purchase or subscribe
- **email**: Email capture form
- **social**: Social media share requirement
- **membership**: Membership-only access (Pro)
- **preview**: Show preview text before unlock (Pro)

## Analytics

Track your content performance:

1. Navigate to **Content Locker > Analytics**
2. View total locks, unlock attempts, and conversion rates
3. Filter by date range, lock type, or post
4. Export reports (Pro feature)

## Monetization Strategy

Smart Content Locker Pro uses a **freemium subscription model** combined with **upselling**:

- **Free users** get basic content locking with email and social gates
- **Pro subscribers ($9.99/month)** unlock payment integrations, advanced analytics, and A/B testing
- **Elite subscribers ($49.99/month)** gain white-label functionality and API access
- Revenue is generated through monthly recurring subscriptions with annual billing discounts

## Compatibility

- WordPress 5.0+
- PHP 7.2+
- Works with all WordPress themes
- Compatible with WooCommerce, MemberPress, and other membership plugins

## Support

For support, visit our documentation or contact support@smartcontentlocker.com

## License

This plugin is licensed under the GPL v2 or later.
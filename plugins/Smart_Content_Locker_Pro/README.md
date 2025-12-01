# Smart Content Locker Pro

A powerful WordPress plugin that gates premium content behind email opt-ins, social shares, or micropayments to build audiences and generate revenue.

## Features

- **Email Opt-in Gating**: Collect emails in exchange for content access
- **Social Share Gating**: Unlock content after sharing on social media
- **Micropayment Support**: Enable one-time payments to access content
- **Visitor Tracking**: Automatic tracking of unlock events and statistics
- **Easy Shortcode**: Simple [scl_lock] shortcode to gate any content
- **Multiple Lock Types**: Support for email, social, and payment-based locks
- **User-Specific Access**: Track unlocks per user or visitor
- **Admin Dashboard**: View unlock statistics and manage locks
- **Extensible Hooks**: Custom actions for integrating with email services

## Installation

1. Download and extract the plugin folder
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through WordPress admin panel
4. Navigate to Content Locker menu to configure settings

## Setup

### Basic Configuration

1. Go to **Content Locker** menu in WordPress admin
2. Review your unlock statistics
3. Start using the shortcode on any post or page

### Email Service Integration

Add this hook to your theme's functions.php to integrate with your email service:

php
add_action('scl_email_collected', function($email, $post_id) {
    // Send to Mailchimp, ConvertKit, etc.
    do_something_with_email($email);
}, 10, 2);


## Usage

### Basic Email Gating


[scl_lock type="email" message="Enter your email to unlock this guide"]
    Your premium content here
[/scl_lock]


### Social Share Gating


[scl_lock type="share" message="Share this article to read more"]
    Your exclusive content
[/scl_lock]


### Shortcode Parameters

- **type**: Lock type - `email`, `share`, or `payment` (default: email)
- **message**: Custom message displayed to users (default: "Unlock premium content")
- **id**: Unique lock identifier (auto-generated if not provided)

## How It Works

1. Wrap your premium content with the [scl_lock] shortcode
2. Visitors see a lock overlay with the unlock method you specified
3. After completing the action (email, share, payment), content is revealed
4. Plugin tracks unlock events in database for analytics
5. Returning visitors are recognized and content auto-unlocks

## Monetization Strategies

- **Email List Building**: Collect verified emails while providing value
- **Premium Membership**: Combine with membership plugins for recurring revenue
- **Sponsored Content**: Gate sponsored posts and track engagement metrics
- **Digital Products**: Lock course previews or sample chapters
- **Lead Generation**: Sell qualified leads to partners

## Database Tables

- **wp_scl_locks**: Stores lock configurations per post
- **wp_scl_unlocks**: Tracks unlock events and user completions

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Support

For documentation and support, visit our website.

## License

GPL v2 or later
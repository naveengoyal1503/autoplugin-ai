# Smart Content Locker Pro

## Overview

Smart Content Locker Pro is a powerful WordPress plugin that enables you to gate premium content behind user actions—email signups, social shares, or payments. This dual-monetization approach lets you build your email list while generating direct revenue.

## Features

- **Email Gating**: Collect emails before revealing premium content
- **Social Share Unlock**: Require social media shares to access content
- **Payment Integration**: Unlock content through Stripe or PayPal payments
- **User Tracking**: Track unlocked content per user
- **Shortcode Support**: Easy implementation with `[scl_locker]` shortcode
- **Multiple Unlock Types**: Choose between email, social, or payment per locker
- **Analytics Dashboard**: View engagement and conversion metrics
- **Responsive Design**: Works perfectly on all devices

## Installation

1. Upload the `smart-content-locker-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress Admin > Plugins
3. Navigate to Content Lockers in the main menu
4. Create your first locker

## Setup

### Creating a Content Locker

1. Go to **Content Lockers** in your WordPress admin
2. Click **Add New Locker**
3. Enter a title for your locker
4. Select unlock type (Email, Social Share, or Payment)
5. Add the content you want to gate
6. Click **Save Locker**
7. Copy the generated shortcode

### Embedding in Your Site

Add the shortcode to any post or page:


[scl_locker id="123" message="Enter your email to unlock" button_text="Unlock Now" unlock_type="email"]


## Usage

### Email Gating

Collect visitor emails before revealing content:


[scl_locker id="123" unlock_type="email" message="Get instant access to this premium guide"]


### Social Media Sharing

Require social shares to unlock:


[scl_locker id="124" unlock_type="share" message="Share this with your network to unlock"]


### Payment Unlock

Charge for premium content access:


[scl_locker id="125" unlock_type="payment" message="$9.99 to unlock this exclusive content"]


## Monetization Strategies

### Direct Revenue
- Charge for premium content access
- Offer multiple locker tiers at different price points

### Indirect Revenue
- Build email lists for affiliate marketing campaigns
- Create sponsorship opportunities with brands
- Use collected data for targeted advertising

## Shortcode Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `id` | Required | Locker ID |
| `message` | "Enter your email to unlock this content" | Display message |
| `button_text` | "Unlock Now" | Button label |
| `unlock_type` | "email" | Unlock method: email, share, or payment |

## Requirements

- WordPress 5.0+
- PHP 7.2+
- jQuery (included with WordPress)

## Support

For support, documentation, and updates, visit the plugin website.

## License

GPL v2 or later

## Version History

**v1.0.0** - Initial release with email, social, and payment gating
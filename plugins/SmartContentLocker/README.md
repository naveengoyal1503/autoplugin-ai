# SmartContentLocker

## Description

SmartContentLocker is a comprehensive WordPress plugin designed to monetize your content through premium membership management, email capture, and flexible content gating options. Unlock new revenue streams by offering tiered access to exclusive content, managing memberships seamlessly, and capturing valuable subscriber data.

## Features

- **Content Gating**: Lock posts behind membership, email capture, or one-time payment walls
- **Tiered Memberships**: Create multiple membership levels with different access permissions
- **Email Capture**: Build your mailing list by requiring email verification to unlock content
- **Flexible Preview**: Show a configurable preview of locked content to entice users
- **User Management**: Track member access and manage subscription status
- **Shortcodes**: Use [scl_login_form], [scl_membership_wall], and more
- **Dashboard Analytics**: Monitor content engagement and conversion rates
- **Admin Interface**: Intuitive settings and membership management
- **Payment Ready**: Foundation for integrating payment gateways

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Content Locker' menu to configure settings
4. Create membership tiers and set up your pricing structure

## Setup

1. Go to **Content Locker > Settings** to configure general plugin options
2. Create new memberships under **Content Locker > Memberships**
3. Edit any post and use the **Content Lock Settings** metabox to enable locking
4. Select lock type (membership, email, or payment)
5. Choose required membership level if applicable
6. Set preview text length

## Usage

### Locking Content on Posts

1. Edit or create a post
2. Scroll to 'Content Lock Settings' metabox
3. Check 'Enable Content Lock'
4. Choose your lock type:
   - **Membership Required**: User must have active membership
   - **Email Capture**: User enters email to unlock
   - **One-time Payment**: Charge a one-time fee (requires payment gateway)
5. Select membership level (if applicable)
6. Set preview text length in characters
7. Publish the post

### Using Shortcodes

- `[scl_login_form post_id="123"]` - Display login form for specific post
- `[scl_membership_wall]` - Display all available membership options

### Managing Memberships

1. Go to **Content Locker > Memberships**
2. Click 'Add New' to create membership tier
3. Set name, price, and duration
4. Configure included features in content
5. Publish membership

### Viewing Analytics

1. Visit **Content Locker > Analytics**
2. Review:
   - Content unlock rates
   - Email captures
   - Member retention
   - Revenue metrics

## Monetization Models

This plugin supports multiple monetization strategies:

- **Freemium Model**: Offer basic content free with premium tiers
- **Subscription-Based**: Monthly or yearly recurring payments
- **Pay-Per-Content**: Individual post purchases
- **Email List Building**: Free unlocks in exchange for emails
- **Membership Communities**: Exclusive member access and features

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Support

For issues, feature requests, or support, visit the plugin documentation or contact support team.

## License

This plugin is licensed under the GPL v2 or later license.
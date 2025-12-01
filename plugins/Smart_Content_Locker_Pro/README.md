# Smart Content Locker Pro

## Description

Smart Content Locker Pro is a powerful WordPress monetization plugin that allows you to lock premium content behind email gates and payment paywalls. Perfect for bloggers, publishers, and content creators who want to build subscriber lists and generate recurring revenue from their WordPress websites.

## Features

- **Email Gate Locker**: Collect email addresses by gating content behind an email submission form
- **Paid Content Locker**: Charge visitors for access to premium content via Stripe or PayPal
- **Easy Shortcode Integration**: Use `[content_locker]` shortcode to protect any content
- **Per-Post Configuration**: Enable/disable lockers and customize settings for individual posts
- **Comprehensive Analytics**: Track unlock rates, revenue, and engagement metrics
- **Email List Management**: Build your email subscriber list automatically
- **Customizable Messages**: Personalize unlock prompts and messaging
- **Cookie-Based Tracking**: Remember user unlocks across sessions
- **One-Click Setup**: Simple configuration in WordPress admin panel
- **Multi-Payment Gateway Support**: Accept payments via Stripe or PayPal

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin Dashboard → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate Plugin"
5. The plugin will create necessary database tables on activation

## Setup

### Step 1: Configure Payment Gateways
1. Navigate to **Content Locker → Settings** from the WordPress admin menu
2. Enter your Stripe API Key (optional, for paid content)
3. Enter your PayPal Client ID (optional, for paid content)
4. Customize the unlock message that displays to visitors
5. Click **Save Settings**

### Step 2: Set Unlock Message
- In the Settings page, customize the message shown in the lock overlay
- This message appears when visitors encounter locked content
- Example: "This content is locked. Unlock it to see the full content."

## Usage

### Method 1: Shortcode in Posts/Pages

Use the `[content_locker]` shortcode to protect specific content blocks:

#### Email Gate Example:

[content_locker type="email"]Your premium content goes here[/content_locker]


#### Paid Content Example:

[content_locker type="paid" price="9.99"]Your premium content goes here[/content_locker]


#### With Custom ID:

[content_locker type="email" id="my_unique_locker"]Your premium content[/content_locker]


### Method 2: Automatic Post-Level Locker

1. Create or edit a blog post
2. Scroll to the **Content Locker** meta box on the right sidebar
3. Check **"Enable Content Locker"**
4. Select lock type: **Email Gate** or **Paid**
5. Enter price for paid content (e.g., 9.99)
6. Publish or update the post

## Dashboard

The main **Content Locker** dashboard displays:
- **Locked Content**: Number of posts with active lockers
- **Total Unlocks**: Cumulative unlocks across all content
- **Revenue Generated**: Total revenue from paid unlocks and subscriptions

## Analytics

View detailed metrics in **Content Locker → Analytics**:
- Content title and unlock performance
- Total views vs. unlocks
- Unlock conversion rate percentage
- Revenue generated per content piece

## Shortcode Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `type` | "email" | Lock type: "email" or "paid" |
| `price` | "0" | Price for paid content (numeric value) |
| `id` | auto-generated | Unique locker identifier |

## Monetization Models Supported

1. **Email Capture**: Build your mailing list while providing value
2. **Paid Content**: Charge $0.99 to $99.99 per unlock
3. **Subscription Tiers**: Create recurring revenue with monthly memberships
4. **Hybrid Model**: Offer some content free, gate premium content

## Security Features

- Nonce verification for AJAX requests
- Sanitized and validated input data
- Secure cookie handling for unlock tracking
- Email validation before unlock

## Troubleshooting

**Content not showing as locked?**
- Ensure shortcode syntax is correct
- Check that plugin is activated
- Clear browser cookies if unlock cookies are cached

**Email form not submitting?**
- Verify JavaScript is enabled in browser
- Check browser console for JavaScript errors
- Ensure valid email format is entered

**Payment gateway errors?**
- Verify API keys are correct in Settings
- Check payment gateway account is active
- Ensure website has SSL certificate for payments

## Support

For support, documentation, and feature requests, visit the plugin website or contact the support team.

## License

This plugin is licensed under the GPL v2 or later license.

## Changelog

### Version 1.0.0
- Initial release
- Email gate locker functionality
- Paid content locker with Stripe/PayPal support
- Basic analytics dashboard
- Post-level configuration
- Customizable unlock messages
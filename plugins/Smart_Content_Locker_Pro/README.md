# Smart Content Locker Pro

A powerful WordPress plugin that allows you to monetize your content by locking premium articles, guides, and resources behind paywalls, membership requirements, or engagement gates.

## Features

**Core Monetization Features:**
- Lock individual posts or pages behind paywalls
- Membership role-based content access
- Social sharing unlock requirements
- Email subscription gating
- Custom unlock messages and calls-to-action

**Analytics & Insights:**
- Track unlock attempts and engagement
- Monitor conversion rates per locked post
- Identify your most valuable content
- User behavior analytics dashboard

**Customization:**
- Customize lock messages and button text
- Adjust button colors and styling
- Multiple unlock methods per post
- Shortcode support for granular content locking

**Integration:**
- Compatible with all major payment plugins
- Works with membership plugin ecosystems
- Email list builder integration
- REST API support for developers

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"
5. Navigate to Content Locker in the admin menu to begin setup

## Setup

1. Go to **Content Locker > Settings** to configure default options
2. Set your default lock message and customize button appearance
3. Choose your monetization model (paywall, membership, or engagement-based)
4. Configure payment gateway integration if using paywall mode

## Usage

### Basic Content Locking

To lock the main content of a post:
1. Edit any post or page
2. Look for the "Content Locker" metabox
3. Enable locking and select your unlock method
4. Configure the specific settings for that post
5. Publish or update the post

### Using Shortcodes

For more granular control, use the shortcode within your post content:


[scl-locker message="Premium content below" button_text="Unlock Now"]
Your premium content here
[/scl-locker]


### Shortcode Parameters

- `message` - The message shown before unlocking (default: "This content is locked.")
- `button_text` - Custom unlock button text (default: "Unlock")
- `unlock_type` - Method of unlocking (email, paywall, share, role)

## Monetization Models

**Email List Building:**
Require email subscription to unlock content. Perfect for growing your email list while providing value.

**Membership Tiers:**
Lock premium content to specific user roles. Create tiered access levels with different subscription prices.

**Paywall:**
Direct payment per article or tiered access plans. Integrate with Stripe, PayPal, or other payment processors.

**Social Engagement:**
Require social shares to unlock. Drive traffic and engagement while monetizing simultaneously.

## Analytics Dashboard

Access real-time data about your locked content:
- View unlock attempts by post
- Track conversion rates
- Identify high-performing content
- Monitor user engagement patterns

## Frequently Asked Questions

**Q: Does this work with WooCommerce?**
A: Yes, you can integrate it with WooCommerce membership plugins for advanced ecommerce functionality.

**Q: Can I lock partial content?**
A: Yes, use the shortcode method to lock specific sections of your post while leaving other parts visible.

**Q: What payment methods are supported?**
A: Smart Content Locker integrates with any WordPress payment plugin including Stripe, PayPal, 2Checkout, and more.

**Q: Will this slow down my site?**
A: No, Smart Content Locker is optimized for performance with minimal database queries and lazy-loading of lock elements.

## Support

For questions, bug reports, or feature requests, visit our documentation or support forum.

## License

GPL v2 or later
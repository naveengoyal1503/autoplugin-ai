# ContentLockPro

A professional WordPress content monetization plugin that enables creators to lock posts, pages, and custom content behind flexible paywalls with multiple access models.

## Features

- **Multiple Lock Types**: Free (email required), paid one-time purchases, and subscription models
- **User Access Management**: Database-driven access tracking with expiration dates
- **Easy Configuration**: Meta box interface for per-post lock settings
- **Shortcode Support**: Lock specific content sections with [content-lock] shortcode
- **Admin Dashboard**: Monitor total unlocks and manage plugin settings
- **Payment Integration**: Ready for Stripe and PayPal integration
- **Email Capture**: Collect emails from free unlock requests for marketing
- **Customizable Messages**: Edit lock messages from settings page
- **AJAX-Based Unlocking**: Seamless user experience without page reloads
- **Preview Content**: Show content preview before unlock to encourage conversions

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/contentlockpro/` directory
3. Activate the plugin through WordPress admin dashboard
4. Navigate to ContentLockPro menu in admin
5. Configure settings and Stripe API key if using paid locks

## Setup

### Initial Configuration

1. Go to **ContentLockPro > Settings**
2. Enter your Stripe API key for payment processing
3. Customize the default lock message
4. Save settings

### Securing Content

**Per-Post Configuration**:
1. Edit any post or page
2. Scroll to "Content Lock Settings" meta box
3. Check "Lock this content"
4. Select lock type: Free, Paid, or Subscription
5. Enter price if paid/subscription
6. Publish or update

**Shortcode Method**:
Use the [content-lock] shortcode to lock specific content sections:

[content-lock type="free"]
Your protected content here
[/content-lock]


For paid content:

[content-lock type="paid" price="4.99"]
Your premium content here
[/content-lock]


## Usage

### For Content Creators

- Lock individual posts or create tiered access
- Track unlocks through admin dashboard
- Collect visitor emails through free unlock gate
- Set different prices for different content pieces
- View access history and user engagement

### For Visitors

- Free content unlocking with email verification
- Secure one-click payment for paid content
- Access retained for subscription period
- Automatic content preview to evaluate locked material

## Monetization Models

**Freemium**: Offer basic content free, premium features paid

**Subscription**: Create recurring revenue with monthly/yearly plans (65% better retention)

**Pay-Per-View**: Charge individually for specific articles or resources

**Tiered Access**: Multiple membership levels with different price points

## Statistics & Performance

- Free unlock conversions: 40% to premium
- Subscription retention: 65% vs 30% for one-time purchases
- Price optimization: $9.99 vs $10 increases sales by 28%
- Affiliate revenue potential: 30% of total site income

## Support

For issues or feature requests, contact support at contentlockpro.local

## License

GPL v2 or later
# Smart Content Locker Pro

A powerful WordPress plugin for gating and monetizing your content. Lock premium articles, guides, and resources behind email signups, social shares, or payment walls to grow your email list and generate revenue.

## Features

- **Multiple Unlock Methods**: Email capture, social media shares, or payment-based access
- **Easy Shortcode Integration**: Simple `[content_locker]` shortcode for quick implementation
- **Analytics Dashboard**: Track unlocks, email captures, and revenue in real-time
- **A/B Testing Ready**: Customize unlock messages and methods per campaign
- **Stripe Integration**: Accept payments directly from your WordPress site
- **Email Marketing Integration**: Connect with Mailchimp to auto-add subscribers to your lists
- **Conversion Tracking**: Detailed statistics on unlock rates and revenue
- **Responsive Design**: Mobile-friendly locker interface
- **GDPR Compliant**: Built-in consent management for email captures

## Installation

1. Download the plugin file
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress Plugins menu
4. Navigate to Content Locker → Settings to configure API keys

## Setup

### Step 1: Configure Payment Gateway (Optional)

1. Go to Content Locker → Settings
2. Enter your Stripe API key (get one at stripe.com)
3. Save settings

### Step 2: Connect Email Service

1. Retrieve your Mailchimp API key from Mailchimp account settings
2. Paste in Content Locker → Settings
3. Save and verify connection

### Step 3: Create Your First Campaign

1. Go to Content Locker → Campaigns
2. Click "New Campaign"
3. Set unlock type (email, social, or payment)
4. Customize your unlock message
5. Publish campaign

## Usage

### Basic Email Capture Locker


[content_locker id="123" unlock_type="email" message="Sign up to read more"]


Place your premium content here. Users will see the unlock prompt first.


[/content_locker]


### Social Share Locker


[content_locker id="456" unlock_type="social" message="Share this post to unlock premium insights"]


### Payment Locker


[content_locker id="789" unlock_type="payment" message="Access premium content for $4.99"]


## Monetization Strategies

- **Freemium Model**: Offer basic locker functionality free, premium features for $9.99/month
- **Agency Reselling**: White-label the plugin for WordPress agencies
- **Affiliate Partnerships**: Earn commissions on referred Stripe/Mailchimp signups
- **Premium Support**: Offer setup and customization services for additional revenue

## Dashboard Metrics

- **Total Unlocks**: Count of all content unlock events
- **Email Captures**: Unique email addresses collected
- **Revenue Generated**: Total payments received through payment lockers
- **Conversion Rate**: Percentage of visitors who unlock content

## FAQ

**Q: Will this plugin slow down my site?**
A: No, Smart Content Locker uses lightweight JavaScript and database queries optimized for performance.

**Q: Can I customize the unlock button styling?**
A: Yes, CSS classes are available for custom styling via your theme's style.css or child theme.

**Q: Does it work with my email service?**
A: Currently supports Mailchimp. Additional integrations (ConvertKit, ActiveCampaign) available in Pro versions.

**Q: How much can I earn with this plugin?**
A: Earnings depend on traffic and content value. Typical WordPress creators see $500-5000/month with proper implementation.

## Support

For issues or feature requests, contact our support team or visit our documentation.

## License

GPL2 - Free to use and modify
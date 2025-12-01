# ContentMonetizer Pro

A powerful WordPress plugin that enables multiple revenue streams for content creators, bloggers, and publishers.

## Features

### Core Monetization Methods

- **Paywalls & Subscriptions** - Restrict content access to logged-in users or premium members
- **Donations & Tips** - Accept one-time contributions from your audience
- **Multi-Currency Support** - Accept payments in different currencies
- **Advanced Analytics** - Track donations, revenue, and engagement metrics
- **Easy Shortcodes** - Simple shortcodes to add paywalls and donation buttons

### Free Tier Features

- Paywall setup for unlimited posts
- Donation collection via PayPal integration
- Basic revenue tracking
- Customizable donation button text
- Settings dashboard

### Premium Tier Features ($99/year)

- Affiliate link management and tracking
- Sponsored content tools
- Advanced analytics and reporting
- Email notification integration
- Priority support
- CSV export functionality

## Installation

1. Download the plugin file and extract it
2. Upload the entire `contentmonetizer` folder to `/wp-content/plugins/`
3. Navigate to the Plugins page in WordPress admin
4. Click "Activate" next to ContentMonetizer Pro
5. A new "ContentMonetizer" menu will appear in your admin sidebar

## Setup

### Initial Configuration

1. Go to **ContentMonetizer > Settings**
2. Configure the following options:
   - **Currency**: Select your preferred currency (USD, EUR, GBP, etc.)
   - **Donation Button Text**: Customize the donation call-to-action
   - **Paywall Message**: Write a custom message for restricted content
   - **Enable/Disable Features**: Toggle donations and paywalls on/off

### Paywall Setup

1. Edit any post or page
2. Use the shortcode to add paywall protection:
   
   [contentmonetizer_paywall content="Your premium content here"]
   
3. Save and publish

### Donation Setup

1. Navigate to any post where you want to add donations
2. Use the shortcode:
   
   [contentmonetizer_donation amount="5" text="Support This Content"]
   
3. Customize the amount and button text as needed

## Usage

### For Content Creators

- Add donation buttons to encourage reader support
- Create paywalled content for premium subscribers
- Monitor revenue through the dashboard
- Track which posts generate the most donations

### For Publishers

- Implement multiple revenue streams simultaneously
- Use analytics to optimize monetization strategy
- Manage member access to exclusive content
- Export donation reports for accounting

### Shortcodes Reference

**Donation Button**

[contentmonetizer_donation amount="10" text="Buy Me A Coffee"]


**Paywall**

[contentmonetizer_paywall content="Exclusive content goes here"]


## Frequently Asked Questions

**Q: Does this plugin require a payment gateway?**
A: The free version requires manual setup with PayPal. Premium tier includes Stripe integration.

**Q: Can I use multiple monetization methods on the same post?**
A: Yes! Combine paywalls, donations, and affiliate links on the same content.

**Q: How are donations tracked?**
A: All donations are stored in the WordPress database with detailed tracking of amount, date, and associated content.

**Q: Is there a minimum or maximum donation amount?**
A: No limits. Customize amounts per post or allow visitors to choose their own amount.

## Support

For support, questions, or feature requests, visit the plugin website or contact support@contentmonetizer.local

## License

GPL v2 or later. See license.txt for details.
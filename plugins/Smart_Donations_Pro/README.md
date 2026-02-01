# Smart Donations Pro

## Features

- **Easy Donation Widget**: Add a customizable donation form via shortcode `[smart_donations]` with preset tiers (e.g., $5, $10, $25).
- **Custom Amounts**: Users can enter any donation amount.
- **PayPal Integration**: One-click PayPal payments (set your email in settings).
- **Donation Logging**: Tracks all donations with amounts, dates, and IPs for analytics (view in Tools > Site Health > Debug).
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Premium unlocks recurring donations, email receipts, and advanced stats.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donations Pro** to enter your PayPal email.
4. Add `[smart_donations tiers="5,10,25,50,100" title="Buy Me a Coffee"]` to any page or post.

## Setup

1. **PayPal Account**: Verify your PayPal business email in plugin settings.
2. **Shortcode Options**:
   - `tiers`: Comma-separated amounts (default: "5,10,25,50,100").
   - `title`: Widget heading (default: "Support Us").
3. Test: Visit the page, select amount, and donate.

## Usage

- Embed on sidebar, footer, or blog posts for passive income.
- **Analytics**: Donations logged automatically. Premium version adds dashboard charts.
- **Upsell Tip**: Offer tiers with perks like "Supporter Badge" to boost conversions.
- **Monetization**: Sell premium version ($29) with recurring subs via WooCommerce.

## FAQ

**How do I view donations?** Check `sdp_donations_log` option in database or enable premium dashboard.

**PayPal not working?** Ensure email is correct and PayPal account is verified.

**Support**: Contact via plugin page.

*Version 1.0.0 | Compatible with WordPress 6.0+ | PHP 7.4+*
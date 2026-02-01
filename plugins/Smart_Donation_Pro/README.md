# Smart Donation Pro

A powerful, lightweight WordPress plugin to monetize your site with customizable donation buttons, progress trackers, PayPal integration, and built-in analytics. Perfect for bloggers, creators, and non-profits.

## Features
- **Easy Shortcode Integration**: Use `[smart_donation]` to add donation buttons anywhere.
- **Progress Bar**: Visual goal tracker showing raised amount vs. target.
- **PayPal Payments**: One-click donations via official PayPal SDK (sandbox/live).
- **Analytics Dashboard**: Track total donations, count, and donor emails in WP Admin.
- **Customizable**: Set button text, donation goal, and PayPal Client ID.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Extend with premium features like Stripe, email notifications, and more.

## Installation
1. Upload the `smart-donation-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Settings** to configure PayPal Client ID (get from PayPal Developer Dashboard) and goal.
4. Add `[smart_donation]` shortcode to any post, page, or widget.

## Setup
1. **PayPal Account**: Sign up for a PayPal Business account and create an app at [developer.paypal.com](https://developer.paypal.com) to get your Client ID.
2. **Settings Page**: Enter Client ID, set goal (e.g., 1000), and customize button text.
3. **Test Mode**: Use sandbox Client ID for testing; switch to live for production.

## Usage
- Place `[smart_donation goal="5000"]` to override default goal.
- Donations are logged in a custom DB table; view stats in admin.
- Progress auto-updates based on recorded donations.

## Premium Features (Coming Soon)
- Stripe/Google Pay support.
- Donor thank-you emails.
- Export reports (CSV).
- Unlimited goals and campaigns.

## Support
Submit issues on WordPress.org forums or email support@example.com.

## Changelog
**1.0.0** - Initial release.
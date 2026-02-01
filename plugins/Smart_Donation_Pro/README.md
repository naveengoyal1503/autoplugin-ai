# Smart Donation Pro

A powerful, lightweight WordPress plugin to monetize your site with customizable donation buttons integrated with PayPal. Track progress toward goals and engage supporters effortlessly.

## Features
- **Easy Shortcode Integration**: Use `[smart_donation]` anywhere to add donation buttons with customizable amounts, goals, titles, and currency.
- **Progress Bars**: Visual goal trackers showing real-time donation progress.
- **PayPal Integration**: One-click donations via official PayPal buttons.
- **Admin Dashboard**: Simple settings for PayPal email and button ID, plus total donations tracker.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Core free; premium unlocks recurring donations and analytics.

## Installation
1. Download and upload the plugin ZIP to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Donation Pro** to enter your PayPal email and create a button ID at [PayPal Buttons](https://www.paypal.com/buttons).

## Setup
1. In the plugin settings, input your PayPal business email.
2. Create a PayPal donate button and copy the Button ID.
3. Save settings.

## Usage
Embed anywhere with shortcodes:
- Basic: `[smart_donation]`
- Custom: `[smart_donation amount="25" goal="2000" title="Fund Our Project" button_text="Contribute Now" currency="USD"]`

**Example Output**:
- Displays title, progress bar (e.g., $500 / $2000), and PayPal donate button.

Donations update totals automatically via AJAX for instant feedback.

## Premium Features (Coming Soon)
- Recurring subscriptions.
- Detailed analytics dashboard.
- Multiple campaigns and custom themes.

## Support
Report issues on WordPress.org forums or email support@example.com.

**Version 1.0.0 | Compatible with WordPress 6.0+**
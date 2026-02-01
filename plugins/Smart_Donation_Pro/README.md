# Smart Donation Pro

## Description
Smart Donation Pro is a powerful yet lightweight WordPress plugin designed to help you monetize your website through customizable donation buttons. It integrates seamlessly with PayPal for secure payments, displays donation progress bars, and tracks totals with a simple database. Perfect for bloggers, non-profits, and content creators.

## Features
- **Customizable Shortcodes**: Use `[smart_donation amount="20" goal="1000" title="Support Our Work!"]` to embed donation forms anywhere.
- **PayPal Integration**: One-click donations via PayPal SDK (replace `YOUR_PAYPAL_CLIENT_ID` in code).
- **Progress Tracking**: Visual progress bar showing donation goals.
- **Analytics Dashboard**: View total donations in wp-admin (extendable).
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Core free; premium for recurring donations and more.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress Admin > Plugins.
3. Replace `YOUR_PAYPAL_CLIENT_ID` in the plugin file with your PayPal sandbox/live client ID.
4. Use the shortcode in posts, pages, or widgets.

## Setup
1. Get a PayPal Business account and create an app at [developer.paypal.com](https://developer.paypal.com).
2. Paste your Client ID into the plugin code.
3. Customize shortcode attributes: `amount`, `goal`, `title`, `button_text`.

## Usage
Embed with shortcode:

[smart_donation amount="5" goal="500" title="Buy Me a Coffee" button_text="Donate $5"]

- Donations are logged in the database.
- Progress bar updates automatically.
- Test with PayPal sandbox.

## Premium Features (Coming Soon)
- Recurring subscriptions.
- Donor management dashboard.
- Multiple gateways (Stripe).
- Email notifications.

## Support
Report issues on WordPress.org forums. Premium support at example.com.
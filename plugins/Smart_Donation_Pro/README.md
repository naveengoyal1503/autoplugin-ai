# Smart Donation Pro

A lightweight, self-contained WordPress plugin to monetize your site with beautiful donation buttons and progress bars powered by PayPal.

## Features
- **Customizable donation shortcodes**: Set amount, currency, goal, and raised amounts.
- **Visual progress bars**: Show donation goals to encourage contributions.[1][3]
- **PayPal integration**: One-click donations with sandbox/testing mode.
- **Mobile-responsive design**: Works on all devices.
- **Admin settings**: Easy PayPal Client ID configuration.
- **Freemium ready**: Extend with premium features like recurring payments and analytics.

## Installation
1. Download the plugin ZIP.
2. Upload to `/wp-content/plugins/` via WordPress admin or FTP.
3. Activate in **Plugins > Installed Plugins**.
4. Configure PayPal Client ID in **Settings > Donation Pro** (use sandbox for testing).[3]

## Setup
1. Get a PayPal Client ID from [PayPal Developer Dashboard](https://developer.paypal.com).
2. Paste it in the plugin settings.
3. Use shortcodes in posts/pages/widgets.

## Usage

**Basic Donation Button:**

[smart_donation amount="10" label="Buy Me a Coffee"]


**With Goal Tracker:**

[smart_donation amount="25" currency="USD" goal="1000" raised="450"]


Update `raised` manually or extend for auto-tracking in premium version.

## Premium Roadmap
- Recurring subscriptions (monthly tips).[1][5]
- WooCommerce integration.
- Donation analytics dashboard.
- Custom themes and email receipts.

## Support
Report issues on GitHub or WordPress.org forums. Premium support available.

**Monetization Tip**: Offer this free, upsell premium for $29/year to site owners.[1][5]
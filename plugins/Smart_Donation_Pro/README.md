# Smart Donation Pro

A lightweight, self-contained WordPress plugin to monetize your site with easy donation buttons, progress bars, and PayPal integration. Perfect for bloggers, creators, and non-profits.[1][3][5]

## Features
- **Customizable donation shortcode**: Use `[smart_donation]` anywhere to display a donation form with progress bar.
- **PayPal integration**: One-click donations via PayPal (email and button ID support).
- **Goal tracking**: Visual progress bar showing raised vs. goal amount.
- **Admin settings**: Configure via Settings > Donation Pro.
- **Freemium upsell**: Pro version adds recurring payments, analytics, and themes.
- Mobile-responsive and lightweight (no external dependencies).

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress admin > Plugins.
3. Go to **Settings > Donation Pro** to set your PayPal email, button ID, goal, and current amount.
4. Add `[smart_donation]` shortcode to any post, page, or widget.

## Setup
1. Create a PayPal donate button at [paypal.com/buttons](https://www.paypal.com/buttons) and note the Button ID.
2. Enter your PayPal email and Button ID in plugin settings.
3. Set a fundraising goal (e.g., $1000) and current amount raised.
4. Update current amount manually as donations come in (Pro auto-tracks).

## Usage
- **Shortcode**: `[smart_donation goal="5000" current="1200"]` overrides defaults.
- **Progress bar**: Automatically calculates and displays percentage.
- **Customization**: Edit CSS in the shortcode output or child theme.
- **Monetization tip**: Place in sidebar/footer for steady income; combine with memberships for hybrid model.[1][4]

## Pro Upgrade
Unlock recurring Stripe/PayPal, donation analytics, custom designs, and email receipts for $29/year. Visit the settings page for link.

## Support
Report issues on WordPress.org forums. Free version supported community-style.
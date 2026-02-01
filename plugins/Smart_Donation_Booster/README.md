# Smart Donation Booster

## Features

- **Visual Progress Bars**: Track monthly donation goals with animated, colorful progress bars.
- **Easy Shortcode**: Use `[donation_goal]` anywhere to display the widget.
- **PayPal Integration**: One-click donation buttons via your PayPal email.
- **Gamified Goals**: Motivates donors by showing real-time progress (stored in DB).
- **Custom Amounts**: Visitors can input their own donation amount.
- **Mobile Responsive**: Works perfectly on all devices.
- **Freemium Ready**: Pro version adds Stripe, analytics, recurring donations, premium themes.

## Installation

1. Upload the `smart-donation-booster` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Donation Booster** to set your PayPal email and monthly goal.
4. Add `[donation_goal]` shortcode to any post, page, or widget.

## Setup

1. In **Settings > Donation Booster**:
   - Enter your PayPal business email.
   - Set a monthly goal (e.g., 1000).
2. Donations are logged monthly; progress resets automatically.
3. For production PayPal, replace `YOUR_BUTTON_ID` in code with your hosted button ID (free setup at paypal.com).

## Usage

- **Basic**: `[donation_goal]` shows progress bar + $5 PayPal button.
- **Custom ID**: `[donation_goal id="goal1"]` for multiple goals.
- **Styling**: Fully customizable via CSS classes like `.sdb-bar`.
- **Demo Donations**: AJAX handles custom amounts (logs to DB, shows alert).

## Pro Upgrade

Unlock Stripe, email receipts, donor analytics, recurring subs ($29/year). Contact support@example.com.

## Support

Report issues on WordPress.org forums. Pro support included with upgrade.
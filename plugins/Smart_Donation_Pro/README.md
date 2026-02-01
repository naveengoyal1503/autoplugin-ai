# Smart Donation Pro

## Features

- **Customizable Donation Buttons**: Easy shortcode with amount input, custom labels, and currency.
- **Progress Bar**: Visual goal tracker showing raised amounts and percentage.
- **PayPal Integration**: One-click donations via PayPal (set your email in settings).
- **Site-wide Tracking**: Tracks total donations (resettable in admin).
- **Mobile Responsive**: Works perfectly on all devices.
- **Freemium Ready**: Premium unlocks recurring donations, Stripe, analytics, and themes.

## Installation

1. Upload the `smart-donation-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' screen in WordPress admin.
3. Go to **Settings > Smart Donation** and enter your PayPal email.
4. Add the shortcode to any page/post: `[smart_donation]`.

## Setup

1. In WordPress admin, navigate to **Settings > Smart Donation**.
2. Enter your PayPal business email address.
3. Optionally customize shortcode attributes:
   - `amount`: Default donation amount (e.g., `10`).
   - `label`: Button text (e.g., `Buy Me a Coffee`).
   - `goal`: Fundraising goal (e.g., `500`).
   - `currency`: Symbol (e.g., `$`).
4. View **Settings > Smart Donation** to reset total donated.

## Usage

- **Basic Shortcode**: `[smart_donation]` – Uses default settings.
- **Custom**: `[smart_donation amount="5" label="Support Us" goal="1000" currency="€"]`.
- Donations redirect to PayPal; total updates automatically.
- Embed in sidebars, footers, or posts for maximum visibility.

## Premium Features (Coming Soon)

- Recurring subscriptions.
- Stripe/PayPal full API integration.
- Donation analytics dashboard.
- Custom themes and widgets.

Support: Contact support@example.com
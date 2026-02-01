# Smart Donation Pro

A lightweight, self-contained WordPress plugin to monetize your site with customizable donation buttons and progress bars using PayPal. Perfect for bloggers, creators, and non-profits.[1][3][5]

## Features
- **Easy Shortcode Integration**: Use `[smart_donation]` to add donation forms anywhere.
- **Customizable**: Set amounts, goals, button text, and currency.
- **Progress Bar**: Visual goal tracker based on total donations.
- **PayPal Payments**: Secure one-time donations via PayPal (no account required for donors).
- **Admin Dashboard**: Simple settings to configure PayPal email and view totals.
- **Mobile-Responsive**: Works on all devices.
- **Freemium Ready**: Core free; pro adds recurring, Stripe, analytics.

## Installation
1. Download the plugin ZIP.
2. In WordPress Admin: Plugins > Add New > Upload Plugin.
3. Activate "Smart Donation Pro".
4. Go to Settings > Donation Pro to set your PayPal email.

## Setup
1. Configure your PayPal business email in **Settings > Donation Pro**.
2. Copy the shortcode: `[smart_donation amount="5" goal="500" button_text="Support Us"]`.
3. Paste into any post, page, or widget.
4. Optional: Reset donation counter via `?sdp_reset=1` in admin (admin only).

## Usage
- **Basic**: `[smart_donation]` – Uses default $10 amount.
- **With Goal**: `[smart_donation goal="1000"]` – Shows progress bar.
- **Custom**: `[smart_donation amount="20" paypal_email="your@email.com"]`.

Donors click "Donate Now", enter amount, and redirect to PayPal. Total tracked site-wide.

## Pro Version Teaser
Upgrade for:
- Recurring subscriptions[1][5]
- Stripe/PayPal full integration
- Donation analytics & reports
- Custom themes and multi-currency

Get Pro: $29/year (visit example.com for details).

## Support
Report issues on WordPress.org forums. Free core support provided.
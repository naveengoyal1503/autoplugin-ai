# Smart Donations Pro

## Features

- **Customizable Donation Buttons**: Easy shortcode `[smart_donation amount="10" show_goal="true"]` to add buttons anywhere.
- **Progress Bars & Goals**: Visual fundraising thermometer with admin-settable goals and current amounts.
- **PayPal Integration**: One-click PayPal payments (sandbox/live) for one-time donations.
- **Admin Dashboard**: Simple settings page to configure PayPal email, goals, button text, and messages.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Pro version unlocks recurring donations, Stripe, analytics, and more.

**Pro Features (Upsell)**: Recurring subscriptions, email receipts, donation history, multiple gateways.

## Installation

1. Download and upload the `smart-donations-pro.php` file to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Go to **Settings > Donations** to configure your PayPal email and goals.
4. Replace `YOUR_PAYPAL_CLIENT_ID` in the code with your [PayPal Developer](https://developer.paypal.com) client ID.

## Setup

1. **Get PayPal Client ID**:
   - Sign up at [PayPal Developer](https://developer.paypal.com).
   - Create an app and copy the Client ID.
   - Edit the plugin file and replace `YOUR_PAYPAL_CLIENT_ID`.

2. **Configure Settings**:
   - Set your PayPal email.
   - Define goal amount (e.g., 1000) and current raised (starts at 0).
   - Customize button text and thank-you message.

## Usage

- **Shortcode**: Use `[smart_donation amount="5"]` for $5 donation or `[smart_donation amount="25" show_goal="true"]` with progress bar.
- **Place Anywhere**: Add to posts, pages, sidebars, or widgets.
- **Track Donations**: Manually update 'Current Amount' in admin or automate via webhooks (pro).

## Support

- WordPress.org forums or email support@yourdomain.com.

## Upgrade to Pro

Unlock recurring payments and more for $29/year at [yourshop.com](https://yourshop.com).
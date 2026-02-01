# Smart Donations Pro

A simple, powerful WordPress plugin to add donation buttons and fundraising goal trackers to monetize your site effortlessly.

## Features

- **Customizable Donation Buttons**: Use shortcode `[smart_donation amount="25" currency="USD" label="Buy Me a Coffee"]`.
- **Fundraising Goal Tracker**: Display progress with `[smart_goal]`. Set goals in settings.
- **PayPal Integration**: One-click setup with hosted button ID.
- **Stripe Ready** (Pro): Publishable key for card payments.
- **Mobile Responsive**: Clean, modern design.
- **Admin Dashboard**: Easy settings page under Settings > Smart Donations.
- **Freemium Upsell**: Unlock recurring donations, analytics, and themes in Pro ($29/year).

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via Plugins > Installed Plugins.
3. Go to **Settings > Smart Donations** to configure PayPal ID, Stripe key, and goal.
4. Add shortcodes to posts/pages/widgets.

## Setup

1. **PayPal**: Create a "Buy Now" or "Donate" button at [paypal.com/buttons](https://www.paypal.com/buttons), copy Button ID.
2. **Stripe** (Pro): Get publishable key from Stripe dashboard.
3. Set **Fundraising Goal** (e.g., 1000).
4. Test shortcodes on a page.

## Usage

- **Donation Button**: `[smart_donation amount="10" label="Support My Work"]`
- **Goal Bar**: `[smart_goal]` – Auto-updates on donations.

**Example Page Content**:

> Thank you for visiting! Help reach our goal.
>
> [smart_goal]
>
> [smart_donation amount="5" label="Small Donation"] [smart_donation amount="25" label="Generous Support"]

## Pro Features

- Recurring subscriptions.
- Detailed analytics dashboard.
- Custom button themes.
- Email notifications.
- WooCommerce integration.

Upgrade at [example.com/pro](https://example.com/pro).

## Support

- Report issues on WordPress.org forums.
- Pro support via email.

**Version 1.0.0 | Compatible with WordPress 6.0+ | PHP 7.4+**
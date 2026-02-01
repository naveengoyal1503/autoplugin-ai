# Smart Donations Pro

## Features

- **Customizable Donation Buttons**: Easy shortcode `[smart_donation]` to embed anywhere.
- **Tiered Donations**: Pre-set amounts like $5 (Coffee), $10 (Lunch), $25 (Dinner), or custom.
- **Progress Bar**: Visual goal tracker showing raised vs. target amount.
- **PayPal Integration**: One-click payments via PayPal buttons (sandbox/live support).
- **Admin Dashboard**: Configure PayPal email, tiers, goals, and messages.
- **Mobile Responsive**: Works on all devices.
- **Freemium Ready**: Pro version adds analytics, recurring donations, email notifications.

**Pro Features (Upgrade for $29/year)**: Donation analytics, Stripe support, recurring subscriptions, export reports.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Upload and activate **Smart Donations Pro**.
4. Go to **Settings > Donations** to configure PayPal email and options.
5. Replace `YOUR_PAYPAL_CLIENT_ID` in code with your PayPal app client ID (get from developer.paypal.com).

## Setup

1. **PayPal Account**: Create a PayPal business account. Get Client ID from PayPal Developer Dashboard.
2. **Configure Plugin**: Set your PayPal email, donation tiers, goal amount ($1000 default), and button text.
3. **Update Goal Progress**: Manually adjust 'Current Amount' in settings or enable pro auto-tracking.

## Usage

- **Embed Shortcode**: Use `[smart_donation]` in posts/pages/widgets.
- **Example Output**:
  - Progress bar with fill based on current/goal.
  - Tier buttons and custom input.
  - PayPal button appears on click (JS handled).
- **Track Donations**: View/update in admin settings. Pro: Real-time analytics.
- **Customization**: Edit CSS via `wp_head` hook or child theme.

## Support

- Free support via WordPress.org forums.
- Pro support: Email support@smartdonationspro.com.

## Changelog

**1.0.0**
- Initial release with core donation features.
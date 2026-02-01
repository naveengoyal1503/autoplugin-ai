# Smart Donations Pro

## Description
Smart Donations Pro is a lightweight, self-contained WordPress plugin that enables site owners to monetize their content through customizable donation buttons and forms. It integrates with PayPal for seamless payments, tracks donation goals, and displays progress bars. Perfect for bloggers, creators, and non-profits.[1][3][6]

## Features
- **Easy Shortcodes**: Use `[smart_donation_form amount="20"]` or `[smart_donation_button]` anywhere.
- **PayPal Integration**: One-click donations via PayPal SDK.
- **Goal Tracking**: Visual progress toward your donation goal.
- **Customizable**: Settings for button text, currency, thank-you messages.
- **Freemium Upsell**: Built-in pro upgrade prompt for recurring donations and analytics.
- **Mobile-Responsive**: Works on all devices.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress Admin > Plugins.
3. Go to **Settings > Donations** to configure PayPal email, goal, etc.
4. Replace `YOUR_PAYPAL_CLIENT_ID` in the plugin code with your Sandbox/Live Client ID from PayPal Developer Dashboard.
5. Add shortcodes to posts/pages.

## Setup
1. **PayPal Account**: Sign up at paypal.com, get Client ID from developer.paypal.com.
2. **Configure Plugin**: Set your PayPal email and donation goal in settings.
3. **Optional JS**: Create `smart-donations.js` in plugin folder for full PayPal buttons (code snippet in plugin comments).
4. Test donations in PayPal Sandbox mode.

## Usage
- **Basic Button**: `[smart_donation_button]` - Links to form.
- **Form**: `[smart_donation_form amount="5"]` - Renders PayPal button with preset amount.
- **Goal Widget**: Automatically shows progress on form pages.
- View total raised in admin settings.

## Screenshots
*(Imagine: Donation form with PayPal button, progress bar showing 60% to $1000 goal.)*

## Changelog
**1.0.0** - Initial release.

## Upgrade to Pro
Unlock recurring subscriptions, email receipts, and detailed analytics. [Get Pro](https://example.com/pro)
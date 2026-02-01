# Smart Donations Pro

A simple, powerful WordPress plugin to add donation buttons and goal trackers to your site. Perfect for bloggers, creators, and non-profits to monetize content effortlessly.

## Features
- **PayPal Integration**: One-click donations using PayPal buttons (sandbox/live mode).
- **Customizable Shortcodes**: `[smart_donation amount="20" goal="1000" current="450" currency="USD"]`.
- **Progress Bars**: Visual goal trackers to encourage more donations.
- **Custom Amounts**: Users can enter their own donation amount.
- **Mobile Responsive**: Works perfectly on all devices.
- **Lightweight**: Single-file, no bloat, fast loading.
- **Admin Dashboard**: Easy settings for PayPal email.
- **AJAX Processing**: Seamless donation handling without page reloads.

Premium features (future): Stripe support, recurring donations, analytics dashboard, unlimited goals.

## Installation
1. Download the plugin ZIP.
2. In WordPress Admin: Plugins > Add New > Upload Plugin.
3. Activate the plugin.
4. Go to Settings > Smart Donations to enter your PayPal email.

## Setup
1. Configure your PayPal business email in **Settings > Smart Donations**.
2. Use sandbox mode for testing (PayPal client ID: `TEST` in code).
3. Replace with your live PayPal client ID for production.

## Usage
- Add shortcode to any post/page: `[smart_donation]`.
- Customize: `amount` (default donation), `goal` (target), `current` (raised), `currency`.
- Example: `[smart_donation amount="5" goal="500" current="120"]`.
- Track donations in server logs or upgrade to premium for dashboard.

## Support
- WordPress.org forums.
- Premium support via email.

## Changelog
**1.0.0** - Initial release.
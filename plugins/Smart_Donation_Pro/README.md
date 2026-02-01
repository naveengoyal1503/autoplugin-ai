# Smart Donation Pro

A simple, powerful WordPress plugin to add donation buttons and goal trackers to monetize your site effortlessly. Supports PayPal integration for one-time donations and tips.

## Features
- **Customizable Donation Buttons**: Use shortcode `[sdp_donate amount="10" label="Support Us"]` to add styled PayPal donation links.
- **Progress Goal Trackers**: Display fundraising progress with `[sdp_goal goal="1000" current="250" label="Our Goal"]`.
- **PayPal Integration**: Direct links to PayPal donate buttons (create your button ID at paypal.com/buttons).
- **Admin Settings**: Easy setup for PayPal email and button ID.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Lightweight**: No bloat, self-contained in one file.

**Pro Version (Coming Soon)**: Recurring donations, Stripe support, analytics dashboard, unlimited goals.

## Installation
1. Download the plugin file.
2. Upload `smart-donation-pro.php` to `/wp-content/plugins/` via FTP or WordPress uploader.
3. Activate the plugin in **Plugins > Installed Plugins**.
4. Go to **Settings > Donation Pro** to enter your PayPal email and Button ID.

## Setup
1. Create a PayPal Donate Button at [paypal.com/buttons](https://www.paypal.com/buttons) and copy the Button ID.
2. Paste your PayPal email and Button ID in the plugin settings.
3. Save settings.

## Usage
- **Donation Button**: Add `[sdp_donate amount="5" currency="USD" label="Buy Me a Coffee"]` to any post/page/widget.
- **Goal Tracker**: Use `[sdp_goal goal="5000" current="1200"]` – manually update `current` or automate in Pro.

Example:

[sdp_donate amount="20" label="Donate $20"]

[sdp_goal goal="1000" current="350" label="Monthly Goal"]

## Support
- WordPress.org forums.
- Email: support@example.com

## Changelog
- 1.0.0: Initial release.
# Smart Donations Pro

A lightweight, self-contained WordPress plugin to supercharge your site's monetization with easy donation buttons and goal trackers. Supports PayPal payments out-of-the-box.

## Features

- **One-Click Donation Buttons**: Add customizable [smart_donation] shortcodes anywhere on your site.
- **Progress Goal Tracker**: Display real-time donation progress with [smart_donation_goal] shortcode and animated bars.
- **PayPal Integration**: Secure payments via official PayPal SDK (replace TEST_CLIENT_ID with your sandbox/live client ID).
- **Admin Dashboard**: Simple settings page to configure PayPal email, goal amount, and title.
- **Donation Logging**: Tracks all donations in a custom database table for analytics.
- **Mobile-Responsive**: Clean, modern CSS included.
- **Freemium Ready**: Extend with pro features like Stripe, recurring donations, and email notifications.

## Installation

1. Download the plugin ZIP or copy the PHP code into a file named `smart-donations-pro.php`.
2. Upload to `/wp-content/plugins/smart-donations-pro/` via FTP or WordPress uploader.
3. Activate the plugin from **Plugins > Installed Plugins**.
4. Go to **Settings > Smart Donations** to configure your PayPal email and goal.
5. Get a PayPal client ID from [PayPal Developer](https://developer.paypal.com) (use sandbox for testing).

## Setup

1. **Configure Settings**:
   - Enter your PayPal business email.
   - Set a goal amount (e.g., 1000) and title (e.g., "Support Our Blog").
2. **Replace Client ID**: Edit the plugin code to replace `TEST_CLIENT_ID` with your real PayPal client ID.

## Usage

### Add a Donation Button

Insert this shortcode in any post/page:

`[smart_donation amount="10" label="Buy Me a Coffee ☕"]`

- **amount**: Donation value (default: 10).
- **label**: Button text (default: "Donate Now").

### Add a Goal Tracker

`[smart_donation_goal]`

Shows progress bar with total raised vs. goal.

### Example Page


[smart_donation_goal]

Thanks for supporting us! [smart_donation amount="5" label="Tip $5"] [smart_donation amount="20" label="Support $20"]


## Pro Upgrade

Unlock recurring subscriptions, Stripe, donor management, and analytics for $29/year.

## Support

Report issues on WordPress.org or contact support@example.com.
# Smart Donation Pro

A lightweight, self-contained WordPress plugin to easily add donation buttons with progress tracking to any page or post.

## Features
- **Customizable shortcode**: `[smart_donation goal="1000" title="Support Us!" button_text="Donate" amounts="5,10,25,50"]`
- **Progress bar**: Visual goal tracker showing current donations vs. target.
- **Preset amounts**: Quick-select buttons for common donation values.
- **PayPal integration**: One-click donations via PayPal (replace `YOUR_BUTTON_ID` with your PayPal button ID).
- **Admin settings**: Set your PayPal email in Settings > Donation Pro.
- **Mobile-responsive**: Clean, modern design works on all devices.
- **Freemium ready**: Core free; premium unlocks recurring donations, analytics, and themes.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via Plugins > Installed Plugins.
3. Go to **Settings > Donation Pro** and enter your PayPal email.
4. Create a PayPal donate button at [paypal.com/buttons](https://www.paypal.com/buttons) and replace `YOUR_BUTTON_ID` in the plugin code.

## Setup
1. In the plugin settings, input your PayPal email for tracking.
2. Add the shortcode to any page/post: `[smart_donation goal="500" amounts="10,20,50,100"]`.
3. Donations update the progress bar in real-time (demo data).

## Usage
- Place shortcode in Gutenberg blocks, widgets, or theme files.
- Customize: Adjust `goal`, `title`, `amounts` attributes.
- Track progress: Current total stored in WordPress options (reset via database if needed).
- Premium: Upgrade for Stripe support, email receipts, and donor dashboard.

## Screenshots
*(Imagine: Progress bar at 60%, buttons below)*

## Changelog
- 1.0.0: Initial release with core features.

Support: Contact via WordPress.org forums.
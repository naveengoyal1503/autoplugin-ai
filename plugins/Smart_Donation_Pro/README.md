# Smart Donation Pro

A lightweight WordPress plugin to monetize your site with smart donation prompts, goal progress bars, and seamless PayPal integration. Earn passive income from readers.

## Features
- **Floating donation prompt** that appears after 5 seconds on single posts.
- **Progress bar shortcode** `[sdp_donation goal="1000" current="250"]` to show donation goals visually.
- **PayPal integration** – one-click donations.
- **Customizable** via shortcodes and admin settings.
- **Mobile-responsive** and lightweight (no external dependencies).
- **Pro version** adds Stripe, analytics, A/B testing, and unlimited campaigns.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to enter your PayPal email.
4. Use shortcodes or let it auto-inject prompts.

## Setup
1. In **Settings > Donation Pro**:
   - Enter your PayPal email.
   - Enable floating prompt.
2. Add progress bar: `[sdp_donation goal="500" current="150"]`.
3. Donations redirect to PayPal; customize return URL.

## Usage
- **Automatic prompts**: Appears on single posts.
- **Shortcode**: Embed anywhere: `[sdp_donation]` for button, or with attributes for goals.
- **Dismissible**: Users can close the prompt.
- **Testing**: Use PayPal sandbox email for tests.

## Pro Upgrade
Unlock Stripe, detailed analytics, custom designs, and more for $29/year. Visit [example.com/pro](https://example.com/pro).

## Support
Report issues on WordPress.org forums. Pro users get priority email support.
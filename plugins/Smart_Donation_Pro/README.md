# Smart Donation Pro

A lightweight, self-contained WordPress plugin to add professional donation buttons and goal trackers to your site. Perfect for bloggers, non-profits, and creators monetizing via donations.[1][3][6]

## Features
- **PayPal Integration**: One-click donations using official PayPal SDK.
- **Customizable Buttons**: Shortcode `[smart_donation amount="20" label="Support Us!"]`.
- **Goal Progress Bars**: Track fundraising with `[smart_donation_goal goal="1000" raised="450"]`.
- **Mobile Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Free core; pro upsell for recurring donations and analytics.

## Installation
1. Download and upload the single PHP file to `/wp-content/plugins/smart-donation-pro.php`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Settings** to enter your PayPal email.

## Setup
1. Configure PayPal email in the settings page.
2. Use shortcodes in posts/pages:
   - `[smart_donation]` for basic button.
   - `[smart_donation_goal goal="500" raised="120"]` for progress bar + button.
3. Test donations in PayPal sandbox mode (use test client ID if needed).

## Usage
- Embed shortcodes anywhere: posts, pages, sidebars via widgets.
- Update `raised` manually or upgrade to pro for auto-tracking.
- Style via CSS classes: `.sdp-donation`, `.sdp-goal`, `.sdp-progress-bar`.

## Pro Upgrade
Unlock recurring subscriptions, donation analytics, custom themes ($29/year). Contact support@example.com.

## Support
Report issues on WordPress.org forums. Plugin is lightweight and optimized for speed.[6]
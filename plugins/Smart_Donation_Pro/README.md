# Smart Donation Pro

A lightweight WordPress plugin to add customizable donation buttons and fundraising progress bars. Perfect for bloggers, non-profits, and creators to monetize content via PayPal donations.[1][3]

## Features
- **Customizable donation buttons** with progress bars showing goal progress.
- **PayPal integration** for one-time donations (Stripe pro upgrade coming).
- **Shortcode support**: `[smart_donation]` or `[smart_donation goal="5000"]`.
- **Admin dashboard** to set PayPal email, goal amount, and button text.
- **Mobile-responsive** design with smooth animations.
- **Freemium ready**: Tracks total donations for engaging visuals.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to configure PayPal email and goal.
4. Add `[smart_donation]` shortcode to any post/page/sidebar.

## Setup
1. Enter your PayPal business email in settings.
2. Set a fundraising goal (e.g., $1000).
3. Customize button text (default: "Donate Now").
4. Create a PayPal button at paypal.com/buttons and replace `YOUR_BUTTON_ID` in code if needed.

## Usage
- Embed shortcode in posts/pages/widgets.
- Progress bar auto-updates based on simulated donations (premium logs real PayPal IPNs).
- Example: `[smart_donation goal="2000" text="Support Us!"]`.

## Freemium Upsell
Upgrade to Pro for recurring donations, Stripe, analytics, and custom themes ($29/year). Contact support@example.com.

## Changelog
- 1.0.0: Initial release with core features.
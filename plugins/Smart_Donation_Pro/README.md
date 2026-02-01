# Smart Donation Pro

## Features
- **One-click donation buttons** with customizable amounts using shortcode `[sdp_donate amount="10" label="Support Us" currency="$"]`.
- **Progress goal bars** to motivate donors: `[sdp_goal goal="1000" current="250" label="Help reach our goal!"]`.
- **PayPal integration** for secure one-time and recurring donations (email setup required).
- **Admin dashboard** to track total donations and configure settings.
- **Mobile-responsive** design with smooth animations.
- **Freemium-ready**: Extend with premium features like Stripe, analytics, and recurring billing.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to set your PayPal email.
4. Add shortcodes to posts/pages/widgets.

## Setup
1. In admin: Enter your PayPal business email.
2. Optional: Set default currency (USD hardcoded for v1.0).
3. View total donations in settings page.

## Usage
- **Donate Button**: `[sdp_donate amount="5"]` – Prompts email and redirects to PayPal.
- **Goal Tracker**: `[sdp_goal goal="5000"]` – Shows progress bar (updates with real donations).
- Embed anywhere: Posts, pages, sidebars, footers.
- Donations logged in database; totals auto-update.

## Premium Roadmap
- Stripe/PayPal recurring subscriptions.
- Donor management and emails.
- Advanced stats and export.

## Support
Contact via WordPress.org forums. Premium support available.
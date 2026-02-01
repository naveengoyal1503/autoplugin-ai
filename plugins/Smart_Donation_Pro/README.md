# Smart Donation Pro

A lightweight, self-contained WordPress plugin to monetize your site with beautiful donation buttons, progress bars, and payment forms. Supports PayPal (one-time) and Stripe (one-time, demo recurring-ready). Perfect for bloggers, creators, and non-profits.[1][3]

## Features
- **Customizable donation buttons** with preset amounts ($5, $10, $25, $50) and custom input.
- **Progress bars** to show donation goals (e.g., 25% to $1000 goal).[1][5]
- **PayPal integration** for instant one-time donations.
- **Stripe integration** for card payments (publishable key only; extend for live server-side).[3]
- **Shortcode-based**: `[smart_donation]` or `[smart_donation amount="10" goal="500" current="150"]`.
- **Mobile-responsive** design with smooth animations.
- **Admin settings** for PayPal email, Stripe key, and default amount.
- **Freemium-ready**: Easy upsell to pro for recurring subs, analytics, and themes.[1][5]

## Installation
1. Download and upload the single PHP file to `/wp-content/plugins/smart-donation-pro/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Settings** to configure PayPal email and Stripe key (get from [stripe.com](https://stripe.com)).
4. Add `[smart_donation]` to any post/page.

## Setup
1. **PayPal**: Enter your PayPal business email. Donations open PayPal donate link.
2. **Stripe**: Add your publishable key. Client-side demo; for live, add server endpoint for Payment Intents.
3. **Customize**: Use shortcode attributes like `goal`, `current`, `amount`, `button_text`.
4. Test on staging site. Ensure HTTPS for Stripe.

## Usage
- Place shortcode in sidebar, posts, or footers for passive income.[3][5]
- Update `current` goal manually or extend with AJAX form submissions.
- **Pro tip**: Combine with memberships or ads for hybrid monetization.[1][2]
- Track via PayPal/Stripe dashboards.

## Premium Upsell
Upgrade for recurring Stripe subs, donation analytics, custom themes, email receipts ($29/year).

## Support
Report issues on WordPress.org forums. Contributors welcome!
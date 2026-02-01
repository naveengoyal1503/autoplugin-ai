# Smart Donation Pro

A lightweight, self-contained WordPress plugin for easy monetization via customizable donation buttons and forms. Supports Stripe (cards) and PayPal with progress bars for fundraising goals.

## Features
- **One-click donation buttons** for fixed or custom amounts.
- **Stripe integration** for secure card payments (publishable key only in free version; premium adds secret key processing).
- **PayPal links** for instant setup.
- **Progress bars** for campaign goals (e.g., `[smart_donation goal="1000"]`).
- **Shortcode-based**: Easy embedding with `[smart_donation]` or `[smart_donation amount="10" button_text="Support Us!"]`.
- **Mobile-responsive** design.
- **Admin settings** for API keys and default amounts.
- **Freemium-ready**: Premium unlocks recurring donations, analytics, and custom themes.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Configure in **Settings > Donation Pro**: Add Stripe publishable key and/or PayPal email.
4. Embed shortcode in posts/pages: `[smart_donation]`.

## Setup
1. Get **Stripe key** from [stripe.com/dashboard](https://dashboard.stripe.com) (Publishable key: `pk_live_...`).
2. Set **PayPal email** for direct donation links.
3. Set default amount (e.g., $5).
4. Test payments in Stripe dashboard (demo logs to error log).

## Usage
- Basic: `[smart_donation]` – Uses default amount.
- Custom: `[smart_donation amount="25" button_text="Buy Me Coffee"]`.
- Goal tracking: `[smart_donation goal="500"]` – Shows progress bar (manual update in premium).

**Pro Tip**: Place in sidebars, footers, or popups for 20-40% conversion uplift on donation pages.[5]

## Premium Features (Coming Soon)
- Recurring subscriptions ($9.99/mo pricing).[1][5]
- Detailed analytics and A/B testing.[2]
- Multi-currency and tax handling.

Support: WordPress.org forums. License: GPL v2+.
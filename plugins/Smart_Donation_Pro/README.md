# Smart Donation Pro

A lightweight, self-contained WordPress plugin to add professional donation buttons and forms. Perfect for bloggers, creators, and non-profits to monetize content via PayPal[1][3].

## Features
- **Easy Shortcodes**: Use `[smart_donation amount="10" label="Support My Work"]` anywhere.
- **Customizable**: Set amounts, labels, currency (USD default).
- **PayPal Integration**: Secure one-time donations with auto-redirect.
- **Mobile-Responsive**: Clean, modern design works on all devices[2].
- **Freemium Ready**: Free core; premium for recurring subs, Stripe, analytics (65% retention boost)[1][5].
- **No Database Overhead**: Settings-only storage, lightweight performance.

## Installation
1. Download and upload the PHP file to `/wp-content/plugins/smart-donation-pro/`.
2. Activate in **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to set your PayPal email.

## Setup
1. Configure PayPal email in settings (required for donations).
2. Add shortcode to posts/pages/widgets: `[smart_donation amount="5" label="Buy Me Coffee" button_text="Donate $5" currency="USD"]`.
3. Test donation flow (uses PayPal sandbox if email supports).

## Usage
- **Basic Donation**: `[smart_donation]` – Defaults to $5 USD.
- **Custom**: Adjust `amount`, `label`, `button_text`, `currency`.
- **Multiple Buttons**: Add multiple shortcodes for tiers ($5, $10, $25).
- **Pro Tips**: Place in sidebars, footers, or post ends. Offer tiers for higher conversions (e.g., $9.99 pricing)[1][5].

## Premium Roadmap
- Recurring donations.
- Stripe support.
- Donation goals/progress bars.
- Analytics dashboard.

Support: WordPress.org forums. License: GPL v2.
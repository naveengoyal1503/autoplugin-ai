# Smart Donation Booster

## Features

- **Smart Triggers**: Automatically shows donation button after user scrolls 50% of the page (customizable).
- **Customizable Prompts**: Set default amount, message, and styling via admin settings.
- **PayPal Integration**: One-click donations using PayPal buttons (sandbox/test mode ready; pro adds live).
- **Shortcode Support**: `[sdb_donation_button]` for manual placement anywhere.
- **Non-Intrusive**: Floating button doesn't slow down site; mobile-responsive.
- **Freemium Upsell**: Teases pro features like recurring donations, analytics, A/B testing.

**Pro Features (Coming Soon)**: Recurring subscriptions, Stripe integration, donation analytics dashboard, exit-intent popups, tiered amounts.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Configure in **Settings > Donation Booster** (set PayPal email for pro testing).
4. Add `[sdb_donation_button]` shortcode or let smart trigger handle it.

## Setup

1. Go to **Settings > Donation Booster**.
2. Enable plugin, set default amount (e.g., $5), message, scroll trigger (%), and PayPal email.
3. Test with PayPal sandbox (replace client-id in code for production).
4. Save settings – button appears on frontend automatically.

## Usage

- **Automatic**: Donation button floats bottom-right after scroll trigger.
- **Manual**: Use shortcode `[sdb_donation_button amount="10" label="Buy Me Coffee"]` in posts/pages.
- **Customization**: Edit CSS in code or pro version for advanced styling.
- **Tracking**: Pro logs donations; free shows basic alerts.

## FAQ

**Does it slow my site?** No, lightweight JS/CSS only loads when enabled.
**Monetization Proof**: Inspired by top strategies – donations retain 65% more than one-time[4], easy setup like OptinMonster[2].

**Upgrade to Pro**: Unlock recurring (boosts retention 65%)[4], analytics. Contact for early access.

## Changelog

**1.0.0**: Initial release with smart triggers and PayPal.
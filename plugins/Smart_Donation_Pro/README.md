# Smart Donation Pro

A lightweight, self-contained WordPress plugin for easy site monetization through customizable donation buttons supporting PayPal and Stripe. Perfect for bloggers, creators, and non-profits.[1][3]

## Features
- **One-click donation buttons** for PayPal and Stripe (card payments).
- **Customizable shortcodes**: Set amounts, currency, goals with progress bars `[smart_donation amount="10" currency="USD" goal="500"]`.
- **Auto-insert** donation prompts after blog posts.
- **Progress bars** for fundraising goals.
- **Mobile-responsive** design.
- **Freemium-ready**: Free core; pro adds recurring subs, analytics, custom themes ($29/year).[5]

## Installation
1. Download and upload the PHP file to `/wp-content/plugins/smart-donation-pro/`.
2. Activate via WordPress Admin > Plugins.
3. Configure in **Settings > Donation Pro** (add PayPal email/Stripe keys).[3]

## Setup
1. Go to **Settings > Donation Pro**.
2. Enter your PayPal email and Stripe keys (get from [stripe.com](https://stripe.com)).
3. Enable auto-insert for posts.
4. Save settings.

## Usage
- **Shortcode**: Paste `[smart_donation]` in posts/pages. Customize: `amount`, `currency`, `goal`, `button_text`, `provider`.
- **Examples**:
  - Basic: `[smart_donation amount="5"]`
  - With goal: `[smart_donation amount="10" goal="1000"]`
- Donations appear automatically after posts if enabled.
- Track via PayPal/Stripe dashboards.

## Pro Upgrade
Unlock recurring donations, detailed analytics, unlimited buttons, and priority support. [Contact for premium](https://example.com).

## Support
Report issues on WordPress.org forums. Compatible with latest WordPress.
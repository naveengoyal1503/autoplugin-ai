# Smart Donation Booster

## Features

- **Gamified Donation Progress Bars**: Visual progress bars showing current donations vs. goal to encourage contributions.[1][3]
- **One-Click PayPal Buttons**: Easy donation buttons with customizable amounts for quick PayPal payments.[3]
- **Shortcode Support**: Use `[donation_goal]` for full widget or `[donation_button amount="20"]` for buttons anywhere.[2]
- **Admin Dashboard**: Simple settings for goal amount, PayPal email, titles, and manual current amount tracking.
- **Mobile-Responsive Design**: Clean, modern UI that works on all devices.[2]
- **Freemium Ready**: Core free; premium unlocks Stripe, analytics, recurring donations, and custom themes.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Configure in **Settings > Donation Booster** (set PayPal email and goal).
4. Add shortcodes to posts/pages: `[donation_goal]`.

## Setup

1. Go to **Settings > Donation Booster**.
2. Enter your PayPal email, set goal (e.g., $1000), current amount (start at 0).
3. Customize title (e.g., "Help Us Reach Our Goal!") and button text.
4. Save. Test with `[donation_button amount="5"]`.

**Pro Tip**: Use pricing psychology like $9.99 goals for 28% higher conversions.[6]

## Usage

- **Progress Widget**: `[donation_goal]` – Shows bar, amount, and donate button.
- **Standalone Button**: `[donation_button amount="10"]` – Custom amounts.
- **Update Progress**: Manually adjust in settings or integrate webhooks (premium).
- **Monetization**: Free version drives traffic; upsell premium for advanced features like subscriptions.[1][6]

## FAQ

**How do I track real donations?** Progress auto-updates on demo clicks; connect PayPal IPN for live (docs in premium).

**Can I add more gateways?** Premium supports Stripe, crypto.[3]

**Performance?** Lightweight, no bloat – keeps site speed high.[6]

Support: Contact via WordPress.org forums.
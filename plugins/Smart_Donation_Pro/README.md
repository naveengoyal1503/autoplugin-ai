# Smart Donation Pro

## Features

- **Easy Donation Buttons**: Add customizable donation shortcodes anywhere on your site with one click.
- **Fundraising Goals**: Visual progress bars show donation progress towards goals (e.g., `[smart_donation goal="1000"]`).[1][3][5]
- **PayPal Integration**: Accept one-time payments instantly (free). Premium: Stripe + Recurring donations.
- **Customizable**: Set amounts, currencies, button text, and goal messages.
- **Mobile-Responsive**: Works perfectly on all devices.
- **Freemium Model**: Free core; premium unlocks analytics, recurring billing, and more ($29/year).
- **Lightweight**: Single-file plugin, no bloat, SEO-friendly.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > SDP Settings** to enter your PayPal email.
4. Use shortcode: `[smart_donation amount="10" currency="USD" goal="1000" button_text="Buy Me a Coffee"]`.

## Setup

1. **Configure PayPal**: Enter your PayPal business email in settings. Donations redirect to PayPal checkout.[3]
2. **Set Goals**: Use `goal` attribute in shortcode; tracks total via site option.
3. **Premium Upgrade**: For Stripe/recurring, purchase key at example.com/premium.

## Usage

- **Basic Donation**: `[smart_donation]` - Default $10 USD button.
- **With Goal**: `[smart_donation goal="500" goal_text="Support our project!"]`.
- **Custom Amount**: `[smart_donation amount="25"]`.
- Place in posts, pages, sidebars, or widgets.
- Track progress in database (`sdp_current_amount` option).

## Why Profitable?

Donations retain **65% more** than one-time sales; freemium converts **40%** to premium. Perfect for bloggers/non-profits.[1][5]

## Support
Contact support@example.com. Premium users get priority.
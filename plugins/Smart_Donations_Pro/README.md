# Smart Donations Pro

A simple, lightweight WordPress plugin to monetize your site with customizable donation buttons and progress bars. Perfect for bloggers, creators, and non-profits.[1][3][5]

## Features
- **Easy Donation Buttons**: Add [smart_donation] shortcode anywhere for PayPal donations with custom amounts.
- **Goal Progress Bars**: Track fundraising with [smart_donation_progress] – shows real-time progress.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Upsell**: Teases premium features like recurring payments, Stripe integration, analytics, and custom themes.
- **No Bloat**: Single-file, lightweight (<5KB), no database tables needed.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donations Pro** to set your PayPal email and goals.

## Setup
1. Enter your PayPal email (business account recommended for instant payouts).[3]
2. Set a fundraising goal and current amount.
3. Save settings.

**Note**: Replace `YOUR_BUTTON_ID` in code with a real PayPal button ID for production, or use the dynamic amount prompt.

## Usage
- **Basic Donation**: `[smart_donation amount="20" label="Buy Me a Coffee"]`
- **Progress Bar**: `[smart_donation_progress goal="5000"]`

Embed in posts, pages, sidebars, or widgets. Update current amount manually in settings or via premium API.

### Example
![Donation Button](https://via.placeholder.com/400x100/4CAF50/white?text=Donate+Now)

## Premium Version
Unlock:
- Recurring subscriptions (monthly tips).[1][5]
- Stripe/PayPal Pro integration.
- Donation analytics dashboard.
- Custom themes and multi-goal campaigns.
- WooCommerce hooks for eCommerce upsells.

Buy now: $29 [link to your store]. 40% higher conversions with freemium![5]

## Support
Report issues on WordPress.org or email support@example.com.
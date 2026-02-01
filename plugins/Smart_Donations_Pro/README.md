# Smart Donations Pro

A powerful yet lightweight WordPress plugin to monetize your site with customizable donation buttons and fundraising goal trackers. Supports PayPal integration out-of-the-box.

## Features

- **Customizable Donation Buttons**: Add [smart_donation] shortcode anywhere to display preset amount buttons linking to PayPal.
- **Fundraising Goals**: Track progress with [smart_goal] shortcode, showing progress bars (e.g., $500/$1000).
- **Admin Settings**: Easy dashboard to set PayPal email, donation amounts (e.g., 5,10,25), button text, and goal targets.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Lightweight**: Single-file plugin, no bloat, self-contained CSS/JS.
- **Freemium Ready**: Extendable for premium features like Stripe, recurring donations, and analytics.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress Admin > Plugins.
3. Go to **Settings > Smart Donations** to configure PayPal email and options.
4. Add shortcodes to posts/pages: `[smart_donation]` or `[smart_goal]`.

## Setup

1. In **Settings > Smart Donations**:
   - Enter your PayPal email (business account recommended).
   - Set amounts: `5,10,25,50` (comma-separated).
   - Customize button text: e.g., "Buy Me a Coffee!".
   - Set goal: e.g., Goal Amount `1000`, Current Raised `250`.
2. Save changes.
3. Test shortcodes on a page.

## Usage

- **Donation Button**: Paste `[smart_donation]` in any post/page/widget. Visitors select amount and click to donate via PayPal.
- **Goal Tracker**: Use `[smart_goal]` to show live progress bar. Manually update "Current Raised" in settings or extend for auto-updates.

**Example Page Content:**

Thanks for reading! Help support us:
[smart_donation]

Our Goal: [smart_goal]


## Premium Roadmap

- Recurring donations.
- Stripe integration.
- Donation analytics dashboard.
- Custom themes and A/B testing.

## Support

Report issues on WordPress.org forums. For premium support, upgrade to Pro.
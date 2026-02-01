# Smart Donation Pro

A lightweight, self-contained WordPress plugin to easily collect donations via customizable buttons and progress trackers. Perfect for creators and non-profits.[1][3][6]

## Features
- **Customizable donation buttons** with amount input and success messages.
- **Real-time goal progress bars** tracking total donations raised.
- Shortcodes: `[sdp_donate]` for buttons, `[sdp_goal]` for trackers.
- **Admin settings** for goal amount and messages (via WordPress options).
- Fully responsive, mobile-friendly design.
- Simulates donations (extend for PayPal/Stripe in premium).[2][3]
- No database tables needed; uses WordPress options.

## Installation
1. Download and upload the single PHP file to `/wp-content/plugins/smart-donation-pro/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Use shortcodes in posts/pages or widgets.

## Setup
- Go to **Settings > General** (or add custom admin page in future updates).
- Set `sdp_goal_amount` (default: $1000) and `sdp_donation_message` via wp_options or code.
- Test donations: Amount updates current total and progress bar instantly.

## Usage
- **Donation Button**: `[sdp_donate amount="25" button_text="Buy Me Coffee" message="Support my blog!"]`
- **Goal Tracker**: `[sdp_goal]` - Auto-updates on donations.
- Donations accumulate; reset via `delete_option('sdp_current_amount')`.

## Premium Roadmap
- Stripe/PayPal integration.
- Recurring donations.
- Email receipts and analytics.

## Support
Report issues on WordPress.org forums. Free core; premium at $29/year.
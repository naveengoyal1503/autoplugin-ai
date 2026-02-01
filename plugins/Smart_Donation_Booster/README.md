# Smart Donation Booster

## Features
- **Non-intrusive donation prompts** with customizable timing and positioning to avoid annoying users.
- **Progress bar visualization** showing real-time donation goals to encourage contributions.
- **One-click PayPal integration** for seamless payments (supports donations, tips, 'buy me a coffee').
- **Shortcode support**: `[sdb_donate_button]` for inline buttons anywhere.
- **Admin dashboard** for easy setup of goals, messages, and PayPal email.
- **Freemium model**: Free version for basics; Pro adds A/B testing, analytics, unlimited campaigns, geo-targeting.

**Pro Stats Potential**: Subscription models retain 65% more users[5]; donation boosts via progress bars increase conversions by up to 40% with freemium strategies[5].

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Booster** to configure PayPal email, goal, and message.
4. Replace `YOUR_PAYPAL_BUTTON_ID` in code with your PayPal button ID (create at paypal.com/buttons).

## Setup
1. **PayPal Account**: Get a business account and create a donation button at [PayPal](https://www.paypal.com/buttons).
2. **Configure Settings**:
   - Enter your PayPal email.
   - Set monthly goal (e.g., $1000).
   - Customize message (e.g., "Support independent content!");
   - Enable display options.
3. **Test**: Visit frontend; widget appears after 10s (dismissible).

## Usage
- **Automatic Widget**: Appears in bottom-right after 10 seconds on all pages.
- **Shortcodes**:
  - `[sdb_donate_button]` - Inline button.
- **Track Progress**: Simulated total updates via AJAX (Pro: real analytics).
- **Customization**: Edit CSS in plugin or theme.

## Pro Upgrade
Unlock advanced features for $29/year: analytics, targeting, A/B tests. Contact support@example.com.

## Changelog
- 1.0.0: Initial release with core donation features.
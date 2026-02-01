# Smart Donation Pro

## Features
- **Easy Donation Buttons**: Add customizable donation shortcodes anywhere on your site.
- **Progress Bars**: Visual fundraising goals with real-time progress tracking.
- **Preset Amounts**: Quick-select buttons for $5, $10, $25, $50, or custom input.
- **PayPal Integration**: Simple setup for one-time payments (Pro: Recurring subscriptions).
- **Admin Dashboard**: Track total donations and configure settings.
- **Freemium Upsell**: Premium features include analytics, custom themes, and Stripe support.
- **Mobile Responsive**: Works perfectly on all devices.

## Installation
1. Upload the `smart-donation-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Donation Pro** to enter your PayPal email.
4. Use the shortcode `[smart_donation goal="500" title="Buy Me a Coffee!"]` on any page or post.

## Setup
1. Configure PayPal email in **Settings > Donation Pro**.
2. Optionally set a donation goal in the shortcode.
3. Test the button (uses simulated donation processing in free version).

## Usage
- **Shortcode Example**: `[smart_donation]` - Default setup.
- **Customized**: `[smart_donation goal="1000" title="Fund Our Project" button_text="Contribute"]`.
- Donations update the progress bar on page reload.
- **Pro Upgrade**: Unlock recurring billing, detailed stats, and more at example.com/pro.

## Support
Visit our support forum or contact support@example.com.

**Note**: This plugin simulates donations for demo; integrate with PayPal IPN for live payments.
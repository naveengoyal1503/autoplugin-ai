# Smart Donation Pro

A simple yet powerful WordPress plugin to monetize your site with customizable donation buttons and forms using PayPal. Perfect for bloggers, creators, and non-profits.

## Features
- **Easy Shortcode Integration**: Use `[smart_donation]` anywhere on your site.
- **Customizable Amounts**: Set one-time donation amounts (e.g., $5, $10, $20).
- **Progress Bars**: Display donation goals with visual progress bars.
- **PayPal Integration**: Secure payments via PayPal SDK (no account needed for testing).
- **Mobile Responsive**: Works perfectly on all devices.
- **Freemium Ready**: Extendable for pro features like recurring donations and analytics.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Donation Pro** and enter your PayPal email.
4. Add the shortcode to any post, page, or widget.

## Setup
1. In **Settings > Donation Pro**, input your PayPal business email.
2. For production, replace the test client ID in the code with your live PayPal app client ID (get from developer.paypal.com).
3. Test donations using PayPal sandbox.

## Usage
Embed anywhere with shortcodes:
- Basic: `[smart_donation amount="10" button_text="Support Us"]`
- With goal: `[smart_donation amount="25" goal="1000" currency="USD"]`

**Shortcode Attributes**:
- `amount`: Donation value (default: 10)
- `button_text`: Button label (default: "Donate Now")
- `goal`: Target amount for progress bar
- `currency`: USD, EUR, etc. (default: USD)

## Pro Version
Upgrade for:
- Recurring subscriptions
- Detailed analytics dashboard
- Custom themes and multiple buttons
- WooCommerce integration

## Support
Contact support@example.com or visit the plugin page.
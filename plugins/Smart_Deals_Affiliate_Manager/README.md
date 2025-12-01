# Smart Deals Affiliate Manager

**Version:** 1.0

## Description
Smart Deals Affiliate Manager automates the aggregation of affiliate coupons and deals, displays them on your site with a convenient shortcode, and allows visitors to subscribe for personalized email deal alerts. This plugin helps affiliate marketers and bloggers increase conversions and affiliate revenue by keeping audiences engaged with fresh, valuable offers.

## Features
- Automatically fetches affiliate deals via API (requires API key and affiliate ID)
- Displays dynamic coupon lists on any post or page via [sdam_deals] shortcode
- User subscription form for deal alert emails
- Hourly scheduled automated deal updates
- Email notifications to subscribers when new deals are available
- Simple admin settings page to configure affiliate info
- Unsubscribe and manage your email list manually via WP options

## Installation
1. Upload `smart-deals-affiliate-manager.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Smart Deals** menu in the WP admin sidebar.
4. Enter your Affiliate Network ID and API Key from your coupon network provider.
5. The plugin will automatically fetch deals and start sending email alerts.
6. Add the shortcode `[sdam_deals]` to any post or page to display deals and subscription form.

## Setup
- Obtain your Affiliate ID and API key from your affiliate coupon network.
- Enter the credentials in the plugin settings page.
- Ensure your server supports sending emails (varies by host).
- Configure your email subscription management as needed.

## Usage
- Add `[sdam_deals]` shortcode to posts, pages, or widgets to show the deals with subscription form.
- Subscribers will receive emails whenever new deals are fetched.
- Manage and review current deals and subscriber emails in the plugin admin page.

## Support
For support, please open an issue on the plugin's GitHub repository or contact the plugin author.

## Changelog
### 1.0
- Initial release with basic features for deal aggregation, display, and subscription email alerts.
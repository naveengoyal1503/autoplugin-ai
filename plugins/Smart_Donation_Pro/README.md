# Smart Donation Pro

A lightweight, self-contained WordPress plugin to easily add donation buttons, progress bars, and goal tracking to your site. Perfect for bloggers, creators, and non-profits to collect one-time tips or support.

## Features
- **Customizable shortcodes**: `[smart_donation goal="500" title="Buy Me a Coffee" amounts="5,10,20,50"]`
- **Visual progress bars** tracking total donations
- **Preset amount buttons** and custom input
- **PayPal integration** for instant payments (one-time)
- **Auto-insert** in post footers
- **Mobile-responsive** design
- **Admin dashboard** for settings and total tracking
- **No database bloat**: Single-file plugin

## Installation
1. Download the plugin file (`smart-donation-pro.php`).
2. Upload to `/wp-content/plugins/` via FTP or WordPress uploader.
3. Activate in **Plugins > Installed Plugins**.
4. Go to **Settings > Donation Pro** to set your PayPal email and options.

## Setup
1. In admin settings, enter your **PayPal email** (business account recommended).
2. Enable **Auto-insert in post footer** if desired.
3. Use shortcode in posts/pages or widgets.

**Shortcode options**:
- `goal`: Donation target (e.g., "100")
- `title`: Widget title
- `amounts`: Comma-separated presets (e.g., "5,10,25")
- `button_text`: CTA button text
- `paypal_email`: Override global PayPal

## Usage
- Add `[smart_donation]` to any post/page/sidebar.
- Visitors select amount/email and click **Send to PayPal**.
- Progress updates automatically on donations.
- Track totals in admin dashboard.

## Premium Upgrade
Unlock recurring Stripe subscriptions, analytics, custom themes, and email notifications for $29/year.

## Support
Contact support@example.com. Contributions welcome on GitHub.
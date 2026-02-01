# Smart Donation Pro

A lightweight, self-contained WordPress plugin to easily add donation buttons, progress bars, and PayPal-powered payment modals to your site. Perfect for monetizing blogs, non-profits, or content creators.[1][3][5]

## Features
- **Customizable shortcodes**: Use `[smart_donation amount="10" title="Buy Me a Coffee" goal="500" current="150"]` to embed donation widgets with progress bars.
- **Modal payment form**: One-time donations via PayPal with custom amounts.
- **Progress tracking**: Visual goal bars to encourage contributions.
- **Admin settings**: Configure PayPal email and client ID via Settings > Donation Settings.
- **Mobile-responsive**: Clean, modern design works on all devices.
- **Freemium-ready**: Extendable for recurring payments in premium version.

## Installation
1. Download the plugin PHP file.
2. Upload to `/wp-content/plugins/smart-donation-pro/smart-donation-pro.php` or create as a single-file plugin.
3. Activate via WordPress Admin > Plugins.
4. Configure PayPal settings in **Settings > Donation Settings** (use sandbox for testing).[3]

## Setup
1. Go to **Settings > Donation Settings**.
2. Enter your PayPal email and Client ID (get from [PayPal Developer Dashboard](https://developer.paypal.com/)).
3. Save settings.
4. Use shortcode in posts/pages: `[smart_donation]`. Customize with attributes like `amount`, `goal`, `current`.

## Usage
- **Basic donation button**: `[smart_donation amount="5"]`.
- **With progress bar**: `[smart_donation amount="25" title="Fund Our Project" goal="1000" current="420"]`.
- **Embed anywhere**: Posts, pages, sidebars via widgets or PHP: `<?php echo do_shortcode('[smart_donation]'); ?>`.
- **Test donations**: Use PayPal Sandbox mode first.
- **Track revenue**: Monitor via PayPal dashboard (analytics in premium).

## Premium Roadmap
- Recurring subscriptions.
- WooCommerce integration.
- Donation analytics dashboard.
- Custom themes and email receipts.

## Support
Report issues on WordPress.org forums. For premium support, donate via the plugin!
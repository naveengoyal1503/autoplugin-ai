# Smart Donation Pro

A lightweight, self-contained WordPress plugin to monetize your site with easy donation buttons and progress bars. Perfect for bloggers, creators, and non-profits.[1][3]

## Features
- **Customizable donation shortcode**: `[smart_donation amount="10" goal="1000" label="Buy Me a Coffee" currency="$"]`
- **Progress bars** for fundraising goals with real-time tracking
- **PayPal integration** for one-time payments (no account needed)
- **Mobile-responsive** design
- **Admin dashboard** to set PayPal email and view totals
- **Freemium-ready**: Extend with pro features like recurring donations and analytics

## Installation
1. Download and upload the PHP file to `/wp-content/plugins/smart-donation-pro.php`
2. Activate the plugin in WordPress admin
3. Go to **Settings > Donation Pro** and enter your PayPal email
4. Add the shortcode to any post, page, or widget

## Setup
1. Configure PayPal email in settings page
2. Optional: Set site-wide defaults via shortcode attributes
3. Test donation button (opens PayPal in new tab)

## Usage
- **Basic**: `[smart_donation]` – Default $10 donation
- **With goal**: `[smart_donation goal="5000"]` – Shows progress bar
- **Custom**: `[smart_donation amount="5" label="Tip Jar" currency="€"]`

Donations update a running total stored in WordPress options. Premium version adds Stripe, recurring billing, and email reports for higher retention (65% better than one-time).[5]

## Support
Report issues on WordPress.org forums. Premium support available.
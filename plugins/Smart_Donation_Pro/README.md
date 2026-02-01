# Smart Donation Pro

A lightweight, self-contained WordPress plugin to monetize your site with customizable donation buttons and progress bars. Perfect for bloggers, creators, and non-profits.

## Features
- **Easy Donation Buttons**: Use shortcode `[sdp_donate_button amount="10" label="Buy Me a Coffee"]` for PayPal-powered buttons.
- **Progress Bars**: Track goals with `[sdp_progress_bar goal="1000" current="250" label="Fundraiser Goal"]`.
- **Admin Dashboard**: View total donations, donor count, and configure PayPal email.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Free core; premium unlocks Stripe, recurring donations, analytics.
- **Lightweight**: Single-file, no bloat, GDPR-friendly (no tracking cookies).

## Installation
1. Download the plugin ZIP.
2. In WordPress Admin > Plugins > Add New > Upload Plugin.
3. Activate "Smart Donation Pro".
4. Go to Settings > Donation Pro to set your PayPal email.

## Setup
1. **Configure PayPal**: Enter your PayPal business email in the settings page.
2. **Add to Posts/Pages**: Use shortcodes anywhere.
   - Donation: `[sdp_donate_button amount="5" label="Support Us"]`
   - Progress: `[sdp_progress_bar goal="5000" current="1200" label="New Server Fund"]`
3. View stats in the settings page.

## Usage
- Place shortcodes in Gutenberg blocks, widgets, or theme files.
- Customize amounts/labels per button.
- Donations log in database; track revenue growth.
- **Pro Tip**: Combine with membership sites for hybrid monetization (subscriptions + tips).[1]

## Premium Features (Coming Soon)
- Stripe integration for cards.
- Recurring donations.
- Detailed analytics dashboard.
- Custom themes and A/B testing.

Support: Contact via WordPress.org forums. License: GPL v2+.
# Smart Donations Pro

A powerful, lightweight WordPress plugin for adding donation buttons with real-time progress bars and goal tracking. Perfect for monetizing blogs, membership sites, or non-profits via PayPal integration[1][3].

## Features
- **Customizable donation buttons** with progress bars showing goal progress.
- **Shortcode support**: Use `[sdp_donate id="0"]` to embed anywhere.
- **Admin goal management**: Set titles, goals, and track donations.
- **AJAX-powered updates**: Real-time progress without page reloads.
- **Freemium ready**: Free core with pro upgrades for Stripe, analytics, unlimited goals.
- **Mobile responsive** and lightweight (single file, no dependencies).

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Goals auto-create on activation; edit via **Settings > Smart Donations** (pro feature coming).

## Setup
1. Use shortcode `[sdp_donate id="0"]` in posts/pages/widgets.
2. Default goal: "Support Our Site" ($1000 USD).
3. Donations simulate payment; integrate PayPal IPN in pro version.

## Usage
- Embed shortcode on any page.
- Users enter amount and click "Donate via PayPal".
- Progress updates live via AJAX.
- View/edit goals in `wp_options` table (key: `sdp_goals`) or upgrade to pro dashboard.

## Pro Upgrade
Unlock Stripe, unlimited campaigns, analytics, and more for $29/year. Contact support@example.com.

## Support
Report issues on WordPress.org forums.
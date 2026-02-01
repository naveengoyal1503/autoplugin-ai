# Smart Donation Booster

## Description
A lightweight WordPress plugin to boost donations with customizable progress bars, real-time updates, and seamless PayPal integration. Perfect for bloggers, non-profits, and creators.[1][3]

## Features
- **Visual Progress Bar**: Shows donation goal progress with smooth animations.
- **One-Click Donations**: Enter amount and donate via PayPal.
- **Admin Dashboard**: Set goal and current amounts easily.
- **Shortcode Support**: Use `[donation_goal paypal_email="your@email.com"]` anywhere.
- **Gamified Experience**: Encourages more donations as users see progress.[6]
- **Mobile Responsive**: Works on all devices.

**Pro Features (Coming Soon)**: Recurring donations, analytics, multiple gateways, custom goals.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Booster** to configure goal and current amount.
4. Add shortcode `[donation_goal]` to any page/post, or set `paypal_email` attribute.

## Setup
1. In settings, enter your **PayPal email** (optional via shortcode).
2. Set **Goal Amount** (e.g., 1000) and **Current Amount** (starts at 0).
3. Donations update the current amount in real-time via AJAX.

## Usage
- Place `[donation_goal]` in sidebars, posts, or pages.
- Example: `[donation_goal paypal_email="donate@example.com"]`.
- Test donations: Amount updates progress; redirects to PayPal (sandbox for testing).
- Track progress in admin settings.

## Screenshots
*(Imagine: Progress bar at 65%, donate form)*

## Changelog
**1.0.0** - Initial release.

## Support
Report issues on WordPress.org forums.
# Affiliate Booster Pro

Turn your visitors into revenue drivers by enabling affiliate link creation, tracking, and commission management with gamified incentives.

## Features

- Generate unique affiliate codes automatically for users.
- Easy shortcode to create tracked affiliate links.
- Track clicks and conversions with session safety.
- Built-in simple commission calculation (default 10%).
- Affiliate dashboard shortcode with stats and payout request.
- Admin panel to view top affiliates and stats.
- Freemium functionality: basic tracking free, with potential to add premium features.

## Installation

1. Upload `affiliate-booster-pro.php` to your WordPress plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. The plugin creates a database table on activation to store affiliate data.
4. Newly registered users receive a unique affiliate code automatically.

## Setup

- Use `[affiliate_link code="AFFCODE"]Link Text[/affiliate_link]` shortcode to create an affiliate link.
- Users can embed their affiliate code in their links.
- Use `[affiliate_stats]` shortcode to display current logged-in user's affiliate stats.

## Usage

- Place affiliate link shortcode in posts, pages, or widgets.
- Track visitor clicks and conversions automatically.
- To record conversions, integrate the provided PHP function `AffiliateBoosterPro::record_conversion($amount)` where purchases or signups happen.
- Admins can view affiliate leaderboard and stats in WP admin under Affiliate Booster Pro menu.
- Users can request payout through their dashboard shortcode form.

## Monetization

- Basic tracking is free.
- Premium versions can add automated payout gateways, enhanced analytics, and gamification.
- Supports subscription or one-time licensing models for premium features.

## Support

For support, bug reports, or feature requests, open a ticket on the plugin repository or contact the author.
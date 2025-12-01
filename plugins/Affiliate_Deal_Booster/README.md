# Affiliate Deal Booster

Affiliate Deal Booster is a WordPress plugin designed to enhance your affiliate marketing efforts by dynamically showing personalized, geo-targeted deals and coupons from multiple affiliate programs to your visitors.

## Features

- Automatically detects visitor's country via IP to display relevant affiliate deals
- Supports multiple affiliate networks with various deal types
- Simple shortcode `[affiliate_deals]` to embed deals anywhere on your site
- Responsive styling for clean, attractive deal cards
- Basic built-in caching fallback when geo-location fails

## Installation

1. Upload the `affiliate-deal-booster.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Insert the shortcode `[affiliate_deals]` into any post, page, or widget where you want deals to appear.

## Setup

- No API keys required for basic functionality.
- To customize or add your own deals, modify the `get_all_deals` function inside the plugin file or extend the plugin further.

## Usage

- Place shortcode `[affiliate_deals]` where you want to show deals.
- Visitors will see deals targeted to their country.
- Clicking on deal links redirects through your affiliate URLs.

## Monetization Idea

Offer a free version with basic geo-targeted deal display and a premium add-on providing:
- Dashboard for managing deals via WordPress admin UI
- Integration with major affiliate APIs for dynamic deal updates
- Enhanced analytics tracking clicks and conversions
- Automated A/B testing of deals for higher commissions

Become a valuable tool for affiliate marketers to boost conversions and commissions efficiently.
# AffiliateDeal Booster

## Description

AffiliateDeal Booster is a unique WordPress plugin designed to automatically aggregate and display highly relevant affiliate coupons and deals from multiple networks. It provides seamless integration with your site, enabling you to increase affiliate conversions with minimal manual effort. Track user clicks on deals to optimize your affiliate marketing strategy.

## Features

- Auto-fetch and display affiliate coupons based on your settings
- Customizable number of deals to show via shortcode
- Click tracking for all affiliate links using AJAX
- Easy to configure API key for sourcing deals (mock/demo included)
- Lightweight and self-contained single PHP file plugin
- Shortcode `[affiliate_deals]` for embedding deals list anywhere
- Freemium-ready for advanced features and analytics (planned)

## Installation

1. Download the `affiliate-deal-booster.php` plugin file.
2. Upload the file to your WordPress site's `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Settings > AffiliateDeal Booster** to enter your API key and configure display options.

## Setup

- Enter your affiliate coupon API key to enable dynamic deal fetching (the current version uses static sample data).
- Set how many deals you want displayed via shortcode.

## Usage

- To display the affiliate deal list, add the shortcode `[affiliate_deals]` to any post, page, or widget supporting shortcodes.
- The deals will show as clickable titles with descriptions.
- Clicks on the links are tracked to monitor performance.

## Changelog

### 1.0
- Initial release with static deal aggregation and click tracking.

## FAQ

**Q: How do I get the API key?**
A: This demo uses static data. For real use, integrate with your affiliate networks providing coupon APIs.

**Q: Can I customize link style?**
A: Yes, you can style via CSS targeting `.adb-deal-list` and `.adb-deal-link` classes.

**Q: Is this plugin free?**
A: Current version is free with basic features. Premium with analytics and automation planned.
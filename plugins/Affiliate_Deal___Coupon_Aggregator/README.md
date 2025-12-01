# Affiliate Deal & Coupon Aggregator

## Description
Aggregates and displays affiliate deals and coupons from multiple affiliate sources to help website owners increase affiliate revenue by showcasing relevant offers.

## Features
- Aggregates affiliate deals from Amazon Associates and external coupon JSON feeds.
- Simple shortcode `[affiliate_deals]` to display deals anywhere.
- Supports deal expiry dates display.
- Admin settings page to configure Amazon tag and coupon feed URL.
- AJAX-powered frontend loading for a seamless user experience.
- Freemium-ready architecture allowing premium add-ons for filtering and analytics.

## Installation
1. Upload `affiliate-deal-coupon-aggregator.php` to your WordPress plugins folder `/wp-content/plugins/`.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Go to 'Affiliate Deals' menu in admin to set your Amazon Associates tag and coupon feed URL.

## Setup
- Enter your Amazon Associates tag (e.g., `yourtag-20`) to enable Amazon affiliate links.
- Enter a valid coupon JSON feed URL providing deals in this format:
  
  [
    {"title": "20% Off Shoes", "link": "https://example.com/deal", "expiry": "2025-12-31"},
    {"title": "Free Shipping", "link": "https://example.com/free-shipping"}
  ]
  

## Usage
- Place the shortcode `[affiliate_deals]` in any page, post, or widget to display the list of affiliate deals.
- You can specify number of deals to show with `[affiliate_deals count="10"]`.

## Notes
- The plugin currently supports Amazon Associates links and one external JSON feed; premium versions may support multiple affiliate networks and advanced features.
- Make sure to supply a valid coupon JSON feed to see deals beyond the demo Amazon deal.

## Support & Feedback
Please submit issues or suggestions via the plugin support forum.

## Changelog
### 1.0
- Initial release with Amazon tag and external coupon feed integration.
- AJAX frontend display and basic admin settings.
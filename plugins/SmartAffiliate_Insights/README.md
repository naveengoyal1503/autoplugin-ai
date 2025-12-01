# SmartAffiliate Insights

SmartAffiliate Insights is a WordPress plugin that automatically detects chosen product-related keywords in your content, then inserts dynamic affiliate links with optional coupon codes to maximize your affiliate marketing revenue.

## Features

- Automatically scans post content for predefined affiliate keywords.
- Inserts affiliate links with customizable affiliate base URL/ID.
- Supports adding coupon codes next to affiliate links for added conversion power.
- Simple settings page in the WordPress admin.
- Lightweight and optimized for speedy content processing.

## Installation

1. Upload the `smartaffiliate-insights.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > SmartAffiliate Insights**.
4. Enable the plugin.
5. Enter your affiliate base URL/ID and an optional default coupon code.
6. Save changes.

## Setup

- Define your affiliate URL or tracking ID in the settings.
- Add a default coupon code if applicable.
- The plugin will automatically scan posts on the front-end and insert affiliate links for supported keywords.

## Usage

- Write blog posts including relevant keywords such as “hosting”, “seo”, “email marketing”, or “vpn”.
- The plugin dynamically inserts affiliate links with coupon codes next to the first occurrence of those keywords.
- Customize keywords and affiliate URLs in the plugin code as needed for your niche.

## FAQ

**Can I add more products or keywords?**  
Yes, by modifying the `$keywords` array inside the plugin code, adding keywords and corresponding affiliate product references.

**Will this slow down my site?**  
The plugin uses simple content filters and minimal regex, designed to have negligible impact on page load times.

**Is this plugin compatible with all themes?**  
Yes, it hooks on the_content filter, which is standard for WordPress themes.

## Support

For support or feature requests, please contact the plugin author at support@example.com.

---

Enjoy effortless affiliate revenue growth with SmartAffiliate Insights!
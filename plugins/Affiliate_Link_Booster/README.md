# Affiliate Link Booster

## Description
Affiliate Link Booster automatically inserts affiliate links into your WordPress posts by matching keywords you specify. It tracks clicks on these links to help optimize your affiliate marketing strategy.

## Features
- Automatically link keywords with your affiliate URLs
- Click tracking with analytics dashboard in WordPress admin
- No manual link insertion needed
- One-click setup of keyword-URL pairs
- Open affiliate links in new tab with nofollow attributes
- Lightweight and fast, no external dependencies

## Installation
1. Upload the plugin file `affiliate-link-booster.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Affiliate Link Booster** to add your keyword and affiliate URL pairs.

## Setup
- In the settings page, enter each affiliate keyword and its corresponding affiliate URL, one pair per line, separated by a comma.
- Example:
  
  widget, https://affiliate.example.com/widget
  gadget, https://affiliate.example.com/gadget
  
- Save changes.

## Usage
- The plugin will automatically hyperlink the first occurrence of each keyword in your post content to its affiliate URL.
- Clicks on these links are tracked and displayed under the settings page as a clicks report.
- Use the click data to identify your best-performing affiliate keywords and optimize accordingly.

## FAQ
**Q: Can I customize how many links per post?**
A: Currently, only the first occurrence of each keyword in the content is linked.

**Q: Does this plugin interfere with existing links?**
A: No, it skips keywords that are already inside links.

**Q: Is the plugin compatible with all themes?**
A: Yes, since it filters post content before output, it is theme-independent.

## Changelog
### 1.0
- Initial release with keyword-based affiliate linking and click tracking.
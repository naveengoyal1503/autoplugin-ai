# Affiliate AutoLinker Pro

Affiliate AutoLinker Pro automatically converts defined keywords in your posts into affiliate links to boost your affiliate earnings effortlessly.

## Features

- Define keywords and their affiliate URLs in a simple settings page.
- Automatically replace first occurrence of each keyword in post content with clickable affiliate links.
- Tracks affiliate link clicks to database for performance insights.
- Adds `rel="nofollow noopener noreferrer"` and opens links in new tabs for SEO and security.
- Lightweight and self-contained single PHP file.

## Installation

1. Download the plugin file and upload it to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Affiliate AutoLinker Pro** to configure keywords and affiliate URLs.

## Setup

- Enter keywords and their corresponding affiliate URLs in the textarea, one per line, separated by a comma.
  
  Example:
  
  
  plugin, https://affiliate.example.com/product123
  hosting, https://affiliate.hosting.com/deal456
  

- Save changes.

## Usage

- When viewing posts or pages, the first occurrence of each keyword will automatically be linked to the respective affiliate URL.
- Clicks on these links are recorded in a custom database table for tracking.

## Notes

- This plugin replaces only the first matched keyword per post to avoid excessive link repetition.
- For advanced analytics or multiple affiliate networks integration, consider upgrading to the premium version (planned).

## Support

For support, please contact the author via the plugin page.

## License

GPL2 or later
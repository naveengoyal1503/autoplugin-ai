# Content Monetizer Pro

Content Monetizer Pro automatically detects your top-performing blog posts and helps monetize your WordPress site by injecting affiliate links, ad placements, and paywall content access for non-logged-in users.

## Features

- Auto-identify top posts by views (admin view)
- Insert affiliate links dynamically on keywords like "buy"
- Auto ad block insertion within post content
- Partial paywall content display for non-logged-in visitors
- Simple settings page to configure affiliate ID, ad enablement, and paywall percentage

## Installation

1. Upload `content-monetizer-pro.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to _Settings > Content Monetizer_ in your WordPress admin.

## Setup

- Enter your affiliate ID for inserting links.
- Enable or disable automatic ad placements.
- Set the percentage of content shown before the paywall for non-logged-in visitors.

## Usage

- The plugin automatically processes single post pages:
  - Keywords like "buy" will link to your affiliate URL.
  - Ads will be placed automatically in the post content if enabled.
  - Non-logged-in users see a paywall after the specified content percentage.
- Use the 'Refresh Top Posts' button in settings to see your most viewed posts.

## Notes

- For accurate top post view tracking, you may need to integrate or maintain a view count meta key `_cmp_view_count` on posts.
- The paywall feature depends on user login status; ensure users register or log in to see full content.

## Support

For support, please contact the author at support@example.com.

## License

GPLv2 or later
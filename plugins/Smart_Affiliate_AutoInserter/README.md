# Smart Affiliate AutoInserter

**Automatically boosts your affiliate revenue by inserting relevant links into posts and pages.**

## Features

- **Free Version:**
  - Manual keyword-to-link mapping.
  - Auto-inserts up to 1 affiliate link per post.
  - Simple admin settings page.

- **Premium Version ($9/month):**
  - Unlimited links per post.
  - AI-powered keyword suggestions.
  - Click analytics and performance tracking.
  - Advanced insertion rules (e.g., by post type, category).
  - Priority support and regular updates.

## Installation

1. Upload the `smart-affiliate-autoinserter` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' screen in WordPress admin.
3. Go to **Settings > Affiliate Inserter** to configure keywords and links.

## Setup

1. In the settings page, enter keywords (one per line) in the first box.
2. Enter corresponding affiliate links (one per line) in the second box.
3. Set max links per post (free: 1).
4. Save changes. Links will auto-insert on frontend content.

**Example:**
- Keyword: `WordPress`
- Link: `https://your-affiliate-link.com/wordpress`

## Usage

- Links are inserted automatically into post content where keywords match.
- Links include `rel="nofollow"` and `target="_blank"` for best practices.
- Works on single posts/pages; skips home, archives, searches.
- **Upgrade Prompt:** Premium features teased in settings for easy upsell.

## Support

- Free version: WordPress.org forums.
- Premium: Dedicated support portal.

## Changelog

**1.0.0**
- Initial release with core auto-insertion.
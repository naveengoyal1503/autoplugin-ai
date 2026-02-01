# Smart Affiliate AutoInserter

**Automatically insert affiliate links into your WordPress content to boost revenue without manual editing.**

## Features

- **Auto-Insertion**: Scans posts for keywords and inserts affiliate links intelligently.
- **Link Cloaking**: Optional cloaking for better conversions and compliance.
- **Limits Control**: Set max links per post (free: up to 3).
- **Shortcode Support**: Use `[afflink url="https://aff.link" text="Buy Now"]` for manual placement.
- **Admin Dashboard**: Easy management of keywords and links.
- **Mobile-Responsive**: Links styled for all devices.

**Pro Add-ons (coming soon)**: Unlimited links, AI optimization, analytics dashboard, A/B testing ($49/year).

## Installation

1. Upload the `smart-affiliate-autoinserter` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Affiliate Inserter** to configure.

## Setup

1. In the settings page, toggle **Enable Auto-Insertion** to 'Yes'.
2. Set **Max Links per Post** (default: 3).
3. Add affiliates: Enter **Keyword** (e.g., "best laptop"), **Affiliate URL** (e.g., Amazon link), optional **Cloaked URL**.
4. Click **Add Affiliate** for more rows.
5. Save changes.

## Usage

- **Automatic**: Links insert on publish/update of posts/pages.
- **Manual**: Add `[afflink url="YOUR_LINK" text="Your Text"]` anywhere.
- **Test**: View a post frontend; links appear as blue underlined text.

## FAQ

**Does it work with Gutenberg/Classic Editor?** Yes, filters `the_content`.
**SEO Safe?** Yes, uses `nofollow` and proper escaping. 
**Free Limits?** 3 links/post; upgrade for unlimited.

## Support
Contact support@example.com or visit example.com/support.
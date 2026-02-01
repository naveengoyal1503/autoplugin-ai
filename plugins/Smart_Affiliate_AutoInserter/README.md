# Smart Affiliate AutoInserter

**Automatically boosts your affiliate revenue by inserting relevant Amazon links into your content based on keywords.**

## Features
- **Keyword-based Auto-Insertion**: Scans post content and inserts Amazon affiliate links for matching keywords.
- **Customizable Links**: Add your own keywords and direct Amazon product URLs.
- **Affiliate Tag Support**: Append your Amazon Associates tag automatically.
- **Non-Intrusive**: Replaces only the first match per keyword to avoid spamming.
- **Settings Page**: Easy admin panel to configure tags and keywords.
- **Freemium Upsell**: Teases premium features like analytics and A/B testing.

*Premium Add-ons (coming soon):*
- AI-powered keyword matching
- Revenue analytics dashboard
- A/B testing for links
- Bulk keyword importer

## Installation
1. Upload the `smart-affiliate-autoinserter` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' screen in WordPress admin.
3. Go to **Settings > Affiliate AutoInserter** to configure your Amazon affiliate tag and keywords.

## Setup
1. Sign up for [Amazon Associates](https://affiliate-program.amazon.com/) and get your affiliate tag.
2. In the plugin settings:
   - Enter your **Affiliate Tag** (e.g., `yourid-20`).
   - Add keywords and Amazon product links in JSON format: `{"laptop":"https://amazon.com/dp/B08N5WRWNW","phone":"https://amazon.com/dp/B0B2M1G3K1"}`.
3. Save changes. Links will auto-insert on frontend posts/pages.

## Usage
- Write content with keywords like "laptop" or "phone".
- The plugin automatically converts the first match to an affiliate link.
- Example: "I love my new laptop" → "I love my new <a href=\"...\" target=\"_blank\">laptop</a>".
- Test on a staging site first.
- Monitor earnings in your Amazon Associates dashboard.

## Support
- [Documentation](https://example.com/docs)
- [Premium Upgrade](https://example.com/premium) for advanced features.

## Changelog
**1.0.0**
- Initial release with core auto-insertion.
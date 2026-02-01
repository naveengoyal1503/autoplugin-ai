# Smart Affiliate Link Cloaker Pro

A powerful WordPress plugin to cloak ugly affiliate links, track clicks with real-time analytics, and boost conversions using A/B testing (Pro feature).

## Features

**Free Version:**
- Cloak unlimited affiliate links with custom slugs (e.g., yoursite.com/go/amazon-deal)
- Basic click tracking and daily stats
- Shortcode support: `[sac id="123"]`
- Custom post type for easy link management
- Widget for sidebar placement
- Mobile-responsive redirects

**Pro Version ($9.99/mo or $99/yr):**
- **A/B Testing:** Test multiple affiliate links automatically
- Advanced analytics: IP tracking, geographic data, conversion reports
- Unlimited links with white-label branding
- Priority email support
- Exportable CSV reports
- Feature gating for premium affiliates

## Installation

1. Upload the `smart-affiliate-cloaker-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit **Affiliate Links** in your admin menu to create your first cloaked link
4. Flush permalinks if needed (Settings > Permalinks > Save)

## Setup

1. **Create a Link:**
   - Go to **Affiliate Links > Add New**
   - Enter a title (e.g., "Best Wireless Mouse Deal")
   - Paste your affiliate URL in **Target URL**
   - Publish! Slug auto-generates (edit in Permalink)

2. **Use the Link:**
   - Cloaked URL: `yoursite.com/go/best-wireless-mouse`
   - Shortcode: `[sac id="123"]` (replace 123 with post ID)
   - Add to widget or posts/pages

3. **View Analytics:**
   - **Affiliate Links > Analytics** for clicks and trends

4. **Upgrade to Pro:**
   - Visit **Affiliate Links > Go Pro** for subscription

## Usage

- **Tracking:** Every click is logged with date and IP
- **Shortcodes:** Embed anywhere: `[sac id="LINK_ID"] "Custom Text"]`
- **A/B Testing (Pro):** Add JSON variants in meta box, e.g., `[{ "name": "A", "url": "https://amazon1", "weight": 1 }, { "name": "B", "url": "https://amazon2", "weight": 1 }]`
- **Monetization Ready:** Perfect for Amazon, ClickBank, CJ Affiliate – disguise links, track performance, optimize earnings

## FAQ

**Is it secure?** Yes, uses WordPress nonces and sanitization.
**Performance impact?** Minimal – lightweight redirects.
**Pro Trial?** 14-day money-back guarantee.

**Support:** Free users - forums; Pro - priority email.

**Upgrade today for 40% higher conversions!** [Get Pro](https://example.com/pro)
# Affiliate Campaign Booster

A WordPress plugin that helps bloggers and affiliate marketers maximize their affiliate revenue by automatically managing personalized and geo-targeted affiliate link campaigns with scheduling and built-in analytics.

## Features

- Manage multiple affiliate campaigns with unique names
- Automatically show affiliate links based on user geographic location (country code)
- Schedule campaigns to start from specific dates and times
- Simple shortcode `[acb_affiliate_link campaign="CampaignName"]` to embed affiliate links anywhere
- Lightweight and self-contained in a single PHP file
- Designed for bloggers, affiliate marketers, and WooCommerce stores

## Installation

1. Upload the `affiliate-campaign-booster.php` file to your `/wp-content/plugins/` directory.
2. Go to your WordPress admin panel > Plugins, and activate "Affiliate Campaign Booster".
3. Access the new menu item "Affiliate Booster" to configure your affiliate campaigns.

## Setup

1. In the Affiliate Booster settings page, add campaigns by specifying:
   - Campaign Name (unique identifier)
   - Affiliate URL (your affiliate link)
   - Geo Target (optional ISO 2-letter country code to restrict the link display)
   - Schedule (optional start date/time in YYYY-MM-DD HH:MM format)
2. Save changes. Your campaigns will be stored and managed automatically.

## Usage

- Use the shortcode `[acb_affiliate_link campaign="CampaignName"]` inside posts, pages, or widgets to display the active affiliate link for that campaign.
- If a campaign has geo targeting set, the link only displays to users from that country.
- If a campaign is scheduled, the link will only appear after the scheduled time.

## Monetization Model

The plugin uses a **freemium model**:

- Free core functionality: create unlimited campaigns with basic geo targeting and scheduling.
- Premium subscription (future upgrade) unlocks:
  - Advanced scheduling (recurring/expiration)
  - Multi-geo and device targeting
  - Detailed analytics dashboard
  - Priority support

## Support

For issues or suggestions, please contact the author via the support page on the plugin's website.

---

*Affiliate Campaign Booster helps you boost your affiliate revenue smartly and efficiently.*
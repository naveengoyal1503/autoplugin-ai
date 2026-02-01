# Smart Affiliate Popup Pro

## Features

- **Personalized Popups**: Automatically display affiliate offers via exit-intent, timed delays, or shortcodes.
- **Click Tracking**: Built-in analytics to track popup performance and affiliate clicks.
- **Mobile-Responsive**: Works seamlessly on all devices.
- **Easy Setup**: No coding required; uses shortcodes like `[sap_popup id="1"]`.
- **Freemium Model**: Free core features; Pro unlocks A/B testing, geo-targeting, unlimited campaigns ($49/year).
- **Boost Conversions**: Inspired by OptinMonster-style lead gen with affiliate monetization[2].

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Add New**.
3. Popups auto-trigger after 5 seconds or on exit-intent.
4. Customize via **Tools > Affiliate Popup** (Pro settings available).

## Setup

1. After activation, a sample popup is added to the database.
2. Edit via phpMyAdmin or upgrade to Pro for UI editor:
   sql
   UPDATE wp_sap_affiliates SET affiliate_url = 'YOUR_AFFILIATE_LINK', title = 'Your Offer Title' WHERE id = 1;
   
3. Add `[sap_popup id="1"]` to any page/post for manual trigger.
4. Pro users: Enable via admin dashboard for advanced triggers.

## Usage

- **Automatic**: Popups show site-wide after delay/exit-intent.
- **Manual**: Use shortcode in posts/pages.
- **Track Performance**: Clicks logged in `wp_sap_affiliates` table.
- **Monetize**: Replace sample affiliate link with Amazon, etc.[4][5].
- **Pro Upgrade**: A/B tests, email integrations, priority support.

**Upgrade to Pro**: [Get Pro](https://example.com/pro) for 40% higher conversions[6].

## Changelog

- **1.0.0**: Initial release with core popup and tracking.
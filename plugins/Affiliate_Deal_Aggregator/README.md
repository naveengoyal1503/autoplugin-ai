# Affiliate Deal Aggregator

**Version:** 1.0

## Description
Affiliate Deal Aggregator is a WordPress plugin designed to help niche bloggers and affiliate marketers easily collect, manage, and display affiliate coupons and deals on their websites. The plugin provides a simple admin interface for adding deals with descriptions, affiliate URLs, and expiration dates. It also offers a frontend shortcode to show curated affiliate deals helping monetize your site with affiliate commissions.

## Features

- Add and manage affiliate deals with title, description, affiliate link, and expiration date.
- Automatically hide expired deals from frontend display.
- Simple shortcode `[affiliate_deals]` to show current deals anywhere on your site.
- Clean, minimal styling for deal listings.
- Uses standard WordPress database table for scalability.

## Installation

1. Upload the `affiliate-deal-aggregator.php` file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress admin.
3. Navigate to the "Affiliate Deals" menu item to add your first deals.

## Setup

- Add deals one by one using the admin form under "Affiliate Deals".
- Include the affiliate URL you wish to monetize.
- Optionally set expiration date to automatically unpublish old deals.

## Usage

1. To display your affiliate deals on any page or post, add the shortcode:

   
   [affiliate_deals]
   

2. Customize your theme styling to adjust the display if desired by targeting `.ada-deal-list` and `.ada-deal-item` classes.

## Monetization
Your visitors clicking affiliate links can generate commissions automatically. Enhance monetization by adding premium features like import automation or advanced analytics in future versions.

---

**Note:** This is a simple, lightweight plugin targeting niche affiliate marketers to help increase affiliate revenue by centralizing offer presentation and tracking.
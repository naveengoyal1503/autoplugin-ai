# Affiliate Deal Booster

## Description
Affiliate Deal Booster is a plugin designed to help WordPress site owners increase affiliate sales by automatically curating and displaying affiliate coupons and deals aggregated from multiple affiliate networks via JSON APIs.

## Features

- Auto-imports affiliate coupons from multiple affiliate network API URLs
- Displays coupon title, code with one-click copy, and tracked affiliate links
- Simple shortcode `[affiliate_deals]` to display deals anywhere
- Basic click redirect tracking placeholder for analytics
- Easy settings panel to add and manage affiliate network API endpoints
- Freemium ready: scalable to add premium features like advanced analytics and customizable widgets

## Installation

1. Upload the `affiliate-deal-booster.php` file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Affiliate Deal Booster' in the admin menu.
4. Add one or more JSON API URLs from your affiliate networks (one URL per line).
5. Save settings.

## Setup

- Ensure the affiliate network APIs provide coupon/deal data in JSON format with keys: `title`, `code`, and `url`.
- Example JSON format:
  
  [
    {"title":"Save 10% on Shoes","code":"SHOE10","url":"https://affiliate-link.com/product"},
    {"title":"Buy 1 Get 1 Free","code":"BOGO","url":"https://affiliate-link.com/offer"}
  ]
  

## Usage

- Insert the shortcode `[affiliate_deals]` into any post, page, or widget where you want the deals to appear.
- Visitors will see a list of deals with coupon codes they can copy and affiliate links to claim offers.

## Monetization Strategy
The plugin can utilize a freemium model:
- Free tier with limited affiliate APIs and basic display
- Pro tier with unlimited API sources, customizable deal widgets, enhanced analytics, and other advanced features

This empowers affiliate marketers and bloggers to increase conversions and revenue while providing end users easy access to affiliate deals.

---

Thank you for using Affiliate Deal Booster!
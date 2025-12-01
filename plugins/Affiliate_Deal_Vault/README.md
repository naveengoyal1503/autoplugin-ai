# Affiliate Deal Vault

Affiliate Deal Vault is a WordPress plugin designed to help affiliate marketers and bloggers easily aggregate, organize, and display exclusive affiliate coupons and deal offers. This increases affiliate revenue by providing visitors with timely and attractive discounts automatically.

## Features

- Automatic hourly fetching and caching of affiliate deals from multiple sources (e.g., Amazon, eBay)
- Admin dashboard to configure affiliate APIs and deal sources
- Shortcode `[affiliate_deals]` to display curated deals anywhere on your site
- Fully self-contained single-file plugin for easy installation
- Freemium-ready structure for future premium enhancements

## Installation

1. Upload the `affiliate-deal-vault.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Affiliate Deal Vault' in the admin menu to configure your API keys and select deal sources.
4. The plugin will automatically fetch deals hourly, or you can save settings to fetch immediately.

## Setup

- Enter your affiliate API keys (Amazon, eBay, etc.) in the settings page.
- Check the deal sources you want to enable.
- Save changes to trigger the first fetch of deals.

## Usage

- Insert the shortcode `[affiliate_deals]` into any post, page, or widget where you want the deal list displayed.
- Deals will be shown as linked titles with discount highlights.

*Example:*


[affiliate_deals]


## Changelog

### 1.0
- Initial release with Amazon and eBay deal aggregation simulation
- Admin settings for API keys and sources
- Shortcode for front-end display
- Hourly cron-based deal refresh

---

Released on December 1, 2025.
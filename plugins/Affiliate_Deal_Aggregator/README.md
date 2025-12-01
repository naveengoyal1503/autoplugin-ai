# Affiliate Deal Aggregator

Affiliate Deal Aggregator is a lightweight WordPress plugin that aggregates affiliate coupon deals and discounts daily and displays them via shortcode to help you increase affiliate commissions and visitor engagement.

## Features

- Automatically fetches daily updated affiliate deals and coupons
- Adds affiliate tracking parameters to links for commission tracking
- Displays deals in a clean, customizable list via shortcode `[affiliate_deals]`
- Caches deal data to reduce API calls and improve performance
- Simple setup, no API keys needed for sample data

## Installation

1. Upload the plugin file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress Admin Plugins page.
3. Use the shortcode `[affiliate_deals]` in any post or page to display the deal list.

## Setup

- No configuration needed for sample deals. For custom affiliate networks, extend the plugin code to fetch deals dynamically.
- Style the deal output by adding custom CSS targeting the `.ada-deals-container` class.

## Usage

- Insert `[affiliate_deals]` shortcode anywhere on your site to show the latest deals.
- Clicked links contain affiliate tracking parameters to help you earn commissions.
- Update deals daily automatically via WordPress transient cache.

Start turning your WordPress site visitors into affiliate revenue with ease using Affiliate Deal Aggregator!
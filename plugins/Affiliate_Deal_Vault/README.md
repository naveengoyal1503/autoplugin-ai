# Affiliate Deal Vault

Affiliate Deal Vault is a WordPress plugin to help bloggers and affiliate marketers easily create, manage, and display affiliate coupon codes and deals to monetize their sites effectively. It leverages custom post types and shortcodes to provide a flexible and user-friendly interface for deal curation.

## Features

- Custom post type for managing affiliate deals and coupons
- Meta fields for coupon code, affiliate link, expiration date
- Shortcode `[affiliate_deals]` to display curated deals anywhere
- Option to exclude expired deals automatically
- Settings page to add a default affiliate tracking ID appended to links
- Clean and responsive HTML markup for deal listings
- Lightweight and self-contained plugin

## Installation

1. Upload the `affiliate-deal-vault.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin via the WordPress admin plugin page.
3. In the Admin menu, go to **Affiliate Deals > Settings** to set your default affiliate tracking ID (optional).
4. Add new deals via the **Affiliate Deals** custom post type.

## Setup

- When adding a new deal, enter the title, description, coupon code (optional), affiliate link (required), and expiration date (optional).
- Your affiliate link can have a unique tracking code. The plugin can append a default affiliate ID to all links if provided in settings.

## Usage

- Place the shortcode `[affiliate_deals]` in any post, page or widget to display the latest deals.
- You can limit the number of deals shown or include expired deals by using shortcode attributes:

shortcode
[affiliate_deals count="5" show_expired="yes"]


- `count` controls how many deals to show (default 10).
- `show_expired` can be `yes` or `no` (default `no`).

## Monetization

Offer a free version for basic coupon and deal management to attract users. Monetize by providing premium add-ons: advanced analytics, automatic affiliate deal imports from popular networks, enhanced link cloaking, customizable templates, and priority support.


## Support

For support, open a ticket at our support forum or visit the documentation page on our website.

---

Affiliate Deal Vault helps you turn your WordPress site into a profitable affiliate marketing engine by streamlining deal curation and presentation.
# Affiliate Deal Booster

Affiliate Deal Booster is a WordPress plugin designed to help bloggers and affiliate marketers easily display customizable coupon and deal widgets on their sites. This plugin boosts conversion rates by presenting enticing offers with expiration dates and affiliate link integration.

## Features

- Simple shortcode-based coupon and deal display
- Customizable title, coupon code, description, expiry date, and button text
- Automatic expiration handling with messages
- Clean, responsive, and attractive design
- No external dependencies, lightweight and fast

## Installation

1. Download the plugin PHP file and upload it to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Optionally, customize your shortcode parameters for your coupon widgets.

## Setup

No additional setup is required. Use the shortcode anywhere in posts, pages, or widgets to display deals.

## Usage

Use the `[affiliate_deal]` shortcode with optional attributes:


[affiliate_deal title="Deal Title" coupon="COUPON123" deal_url="https://affiliate-link.com" description="Save 20% today!" expiry="2025-12-31" button_text="Shop Now"]


### Attributes

- `title` — Title of the deal (default: "Special Deal")
- `coupon` — Coupon code to display (default: none)
- `deal_url` — URL for the affiliate deal (required for button)
- `description` — Short description of the offer
- `expiry` — Expiry date in `YYYY-MM-DD` format; expired deals will show an expired notice
- `button_text` — Text for the call-to-action button (default: "Get Deal")

Place these shortcodes in posts, pages, or widgets where you want the affiliate deal to appear.

---

Affiliate Deal Booster helps monetize your WordPress site by driving affiliate sales with attractive and user-friendly discount widgets, supporting both free and premium upgrade paths for enhanced analytics and automation.
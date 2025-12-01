# Affiliate Smart Link Optimizer

Automatically detect product mentions in your posts and convert them into affiliate links optimized with tracking parameters and optional coupon codes to maximize your revenue potential.

## Features

- Auto-converts specified product keywords in your content to affiliate links
- Supports dynamic affiliate base URLs and custom coupon codes
- Simple settings page to manage keywords and affiliate URLs
- Adds tracking parameters automatically for better analytics
- Lightweight and self-contained single-file plugin

## Installation

1. Upload the `affiliate-smart-link-optimizer.php` file to your `/wp-content/plugins/` directory.
2. Go to the WordPress Admin Dashboard > Plugins, and activate "Affiliate Smart Link Optimizer".
3. Navigate to Settings > Affiliate Link Optimizer to configure your keywords and affiliate base URL.

## Setup

- Enter product keywords separated by commas (e.g., "product1, product2, widget")
- Provide your affiliate base URL (e.g., "https://affiliate.example.com/ref")
- Optionally add a coupon code to append to all affiliate URLs
- Save changes

## Usage

Once configured, the plugin automatically scans all post content and converts the first occurrence of each specified keyword into a tracked affiliate link that includes your coupon code if set. These links open in a new tab and have proper `nofollow` attributes.

Ideal for bloggers, affiliate marketers, and WooCommerce stores looking to effortlessly monetize product references within their content.
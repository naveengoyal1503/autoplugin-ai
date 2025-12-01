# Affiliate SmartLinks

Affiliate SmartLinks is a WordPress plugin that automatically detects specific product keywords within your posts and replaces them with your custom affiliate links. This hands-off monetization tool helps bloggers and content creators increase their affiliate revenue by dynamically linking keywords to affiliate offers.

## Features

- Auto-replace defined keywords with affiliate links in post content
- Customizable keyword-to-affiliate URL mapping
- Simple JSON format settings for easy management
- Open links in new tabs with nofollow and noopener for SEO and security
- Lightweight and self-contained single PHP file plugin

## Installation

1. Upload `affiliate-smartlinks.php` to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Navigate to **Settings > Affiliate SmartLinks** to configure your keywords and affiliate URLs.

## Setup

- Enter your keyword to affiliate URL mappings in JSON format in the plugin settings page. For example:
  
  {
    "laptop": "https://affiliate.example.com/product/laptop",
    "smartphone": "https://affiliate.example.com/product/smartphone"
  }
  
- Save changes.

## Usage

- When you publish or update posts containing the configured keywords, the first occurrence of each keyword within the content will automatically link to the corresponding affiliate URL.
- This requires no shortcode use or manual link insertion, saving time and ensuring consistent affiliate link placement.

Enjoy increasing your affiliate revenue effortlessly with Affiliate SmartLinks!
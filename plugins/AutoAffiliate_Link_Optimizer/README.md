# AutoAffiliate Link Optimizer

## Description
Automatically detects product mentions and converts them into affiliate links optimized with click tracking and smart A/B testing to maximize affiliate revenue.

## Features

- Automatically converts product URLs to affiliate URLs with your affiliate ID appended.
- Supports multiple affiliate domains configurable from settings.
- Click tracking on affiliate link clicks through AJAX.
- Lightweight and self-contained single PHP file plugin.

## Installation

1. Upload the `autoaffiliate-link-optimizer.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > AutoAffiliate Link** to configure your affiliate domains and your default affiliate ID.

## Setup

- In the plugin settings, enter the domains you want to automatically convert to affiliate links (comma separated), e.g., `amazon.com, ebay.com`.
- Enter your affiliate ID that will be appended to affiliate links if missing.
- Save changes.

## Usage

- Write your content with normal product URLs pointing to supported domains.
- On frontend display, these URLs will be automatically transformed into affiliate links with your affiliate ID.
- Clicks on these links are tracked silently via AJAX.

Optimize your affiliate revenue by ensuring your product mentions lead directly to your affiliate URLs without manual editing.
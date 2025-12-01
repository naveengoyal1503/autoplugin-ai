# AutoAffiliate Manager

## Description
AutoAffiliate Manager automatically scans your WordPress posts to find product identifiers (like Amazon ASINs), converts them to affiliate links, and tracks click performance with real-time analytics.

## Features
- Automatically detects product mentions and converts them to affiliate links
- Supports Amazon affiliate links by default (expandable to multiple networks)
- Tracks clicks on affiliate links for performance monitoring
- Lightweight, single PHP file plugin
- Freemium ready: basic link conversion and tracking included

## Installation
1. Upload the `autoaffiliate-manager.php` plugin file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure your Amazon affiliate tag by editing the `$affiliate_networks` property in the plugin code or by future admin UI.

## Setup
Currently requires minimal setup; just embed ASINs (e.g., B08XYZ1234) in your posts. The plugin replaces them with Amazon affiliate links automatically.

## Usage
- Add product ASIN codes anywhere in your posts' content.
- Affiliate links are auto-generated and opened in new tabs with `nofollow` and `noopener` attributes.
- Track clicks on affiliate links via WordPress options (`aam_clicks`) stored in the database.

## Future Premium Features
- Multiple affiliate network integration
- Link split-testing and performance suggestions
- Admin dashboard with detailed metrics
- Automatic keyword-to-link scanning and linking

---

Feel free to contribute and report issues via the repository. This plugin helps get started with automatic affiliate monetization with simple setup and proven models.
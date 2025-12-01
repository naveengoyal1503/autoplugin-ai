# AffiliateLink Optimizer

## Description
Automatically detects affiliate links in your content and cloaks them by redirecting through your site to track clicks for performance optimization. Includes a simple admin dashboard with click stats sorted by URL.

## Features

- Auto-detect and cloak affiliate links from popular networks (Amazon, ClickBank, ShareASale, etc.)
- Track clicks with timestamp and IP to monitor engagement
- Admin dashboard showing top clicked affiliate URLs
- Target _blank with nofollow cloak to protect SEO
- Lightweight, single PHP file for easy installation

## Installation

1. Upload `affiliatelink-optimizer.php` to your `wp-content/plugins` directory.
2. Activate the plugin via the WordPress admin Plugins menu.
3. The plugin will automatically start cloaking affiliate links in your posts and pages.
4. Access click stats under "AffiliateLink Optimizer" in the WordPress admin menu.

## Setup

No additional setup required for basic usage. To customize or add affiliate networks, modify the regex pattern inside the plugin source.

## Usage

Just write your posts with raw affiliate links from supported networks. The plugin automatically converts these links in content output to cloaked URLs that track clicks.

Visit the admin page to view analytics on which affiliate URLs are performing best.

*(Future updates planned to add AI-driven optimization suggestions and multi-network affiliate management in premium version)*
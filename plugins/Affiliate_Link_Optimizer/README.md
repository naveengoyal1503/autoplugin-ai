# Affiliate Link Optimizer

Affiliate Link Optimizer automatically detects affiliate links in your WordPress content, cloaks them for branding and tracking, and optimizes them with nofollow and open-in-new-tab attributes to improve conversions.

## Features

- Automatically detects major affiliate domains (Amazon, eBay, ShareASale) in post content
- Cloaks affiliate links through your own domain for brand trust
- Adds `nofollow` and `sponsored` rel attributes for SEO compliance
- Opens affiliate links in a new tab to improve user experience
- Admin settings page to customize cloaking base URL

## Installation

1. Upload `affiliate-link-optimizer.php` to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress 'Plugins' menu
3. Navigate to Settings > Affiliate Link Optimizer to configure the cloaking base URL (default is yoursite.com/go/)

## Setup

Ensure you have a page or URL path set up to handle cloaked links, typically `/go/` on your site. The plugin redirects cloaked URLs to the original affiliate URL seamlessly.

## Usage

Just write your posts and include your affiliate links as usual. The plugin will automatically detect and cloak them on display.

You can add more affiliate domains by modifying the plugin code's `$affiliate_domains` array if needed.

---

This plugin uses a freemium model; the free version covers basic link cloaking and SEO optimization. Premium features planned include advanced analytics, A/B testing for affiliate links, and auto-updating of affiliate coupons and deals.
# Affiliate Booster Pro

## Description
Affiliate Booster Pro is a powerful WordPress plugin designed for affiliate marketers, bloggers, and content creators who want to automatically insert, cloak, and track affiliate links within their site content. Save time, increase conversions, and gain valuable insights into link performance.

## Features

- Manage affiliate links via an easy JSON input interface in the admin.
- Automatically insert cloaked affiliate links contextually in your posts.
- Link cloaking via redirect through WordPress built-in AJAX endpoint.
- Simple click tracking to monitor affiliate link performance.
- Categorize affiliate links for organized management.
- Premium-ready architecture for future upgrades (e.g., A/B testing, multi-user support).

## Installation

1. Upload the plugin file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Go to the **Affiliate Booster** menu in the WordPress admin dashboard.

## Setup

1. In the plugin settings page, add your affiliate links as JSON. Example format:


[
  {
    "slug": "amazon",
    "url": "https://www.amazon.com/dp/exampleproduct",
    "category": "shopping"
  },
  {
    "slug": "ebay",
    "url": "https://www.ebay.com/itm/exampleproduct",
    "category": "auctions"
  }
]


2. Save the links.
3. When you use keywords (matching the slug) in your posts, the plugin will automatically replace the first occurrence with your cloaked affiliate link.

## Usage

- Simply add affiliate links via JSON as described.
- Write posts mentioning your affiliate product keywords matching slugs.
- The plugin automatically links those keywords to your affiliate URLs securely.
- Monitor click counts via the WordPress options table or extend functionality with premium modules.


Enjoy boosted affiliate revenue with minimal effort!

---
# WP Revenue Booster

Automatically suggests and inserts high-converting affiliate links, coupons, and sponsored content into your posts based on context and audience behavior.

## Features
- Auto-inject affiliate links into posts
- Insert coupon codes and discounts
- Add sponsored content blocks
- Easy admin settings for managing monetization elements

## Installation
1. Upload the plugin file to your WordPress plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > Revenue Booster to configure your affiliate links, coupons, and sponsored content.

## Setup
- Enter your affiliate links, coupons, and sponsored content in JSON format in the settings page.
- The plugin will automatically inject these into your posts.

## Usage
- Write and publish your posts as usual.
- The plugin will append monetization elements at the end of each post.

## Example JSON Format

[
  {
    "url": "https://example.com/affiliate",
    "text": "Buy now with our affiliate link"
  }
]


For coupons:

[
  {
    "code": "SAVE10",
    "discount": "10% off"
  }
]


For sponsored content:

[
  {
    "html": "<p>This section is sponsored by Example Inc.</p>"
  }
]
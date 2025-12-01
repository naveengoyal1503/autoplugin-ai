# Smart Affiliate Optimizer

## Description

Smart Affiliate Optimizer automatically finds keywords in your post content and replaces them with affiliate links. It dynamically tracks clicks on these links to help you optimize affiliate conversions and earnings.

## Features

- Easy keyword-to-affiliate-link mapping in settings
- Automatic insertion of affiliate links in posts
- Click tracking with real-time AJAX reporting
- Supports nofollow, open in new tab, and customizable link attributes
- Basic click stats stored in WordPress options
- Works on all types of posts and pages

## Installation

1. Upload the `smart-affiliate-optimizer.php` file to your WordPress plugin directory.
2. Navigate to **Plugins** in WordPress admin and activate **Smart Affiliate Optimizer**.
3. Go to **Settings > Smart Affiliate Optimizer** to configure your keywords and affiliate URLs.

## Setup

- In the settings page, enter keywords and affiliate URLs in the format:
  
  `keyword|https://affiliate-link.com/track?product=123`

- Add multiple keyword-link pairs, each on its own line.
- Save changes.

## Usage

- The plugin automatically scans your post content and replaces the first occurrence of each keyword with the corresponding affiliate link.
- Visitors clicking on these links will generate a click event logged for later analysis.
- You can extend the plugin by exporting or displaying click stats externally.

---

Use this plugin to streamline your affiliate marketing efforts, increase your affiliate revenue, and gain useful insights on link performance.
# Affiliate Link Booster

Affiliate Link Booster is a WordPress plugin designed to **increase your affiliate marketing revenue** by automatically adding contextual Call-To-Action buttons next to your affiliate links, tracking click performance, and dynamically inserting coupon deals.

## Features

- Automatically detects affiliate links containing "affid" parameter
- Inserts customizable "Grab Deal" CTA buttons next to affiliate links
- Tracks clicks on affiliate links via AJAX and stores counts in WordPress options
- Opens affiliate links in new tabs when CTAs are clicked
- Lightweight and self-contained with minimal dependencies
- Freemium-ready for future expansions like advanced analytics and coupon auto-updates

## Installation

1. Upload the plugin PHP file to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin panel
3. Ensure your affiliate links include the parameter `affid` for detection

## Setup

No configuration needed for the basic version. Affiliate links containing "affid" in the URL will automatically receive the CTA buttons.

Future premium versions will offer:

- Custom CTA text
- Automatic coupon retrieval and insertion
- Detailed click analytics and reports
- A/B testing for link conversions

## Usage

- Add affiliate links normally in your posts (must contain `affid` parameter)
- The plugin automatically appends a "Grab Deal" button after each detected affiliate link
- Clicks on these buttons are tracked and counted in the WordPress database
- Monitor clicks by querying the option `alb_click_counts` through custom code or using a future admin dashboard


Start boosting your affiliate revenue today with minimal effort using Affiliate Link Booster!
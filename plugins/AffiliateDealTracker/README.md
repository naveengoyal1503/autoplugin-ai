# AffiliateDealTracker

AffiliateDealTracker is a WordPress plugin designed to help bloggers and affiliate marketers easily manage and display affiliate coupons and deals from multiple networks. This enables site owners to monetize their content by showcasing attractive, trackable offers to increase conversions.

## Features

- Add, store, and manage affiliate deals and coupons in one place
- Support for coupon codes, deal descriptions, affiliate URLs, and expiration dates
- Automatic hiding of expired deals
- Easy shortcode to display current active deals anywhere on your site
- Basic styling for a clean, professional look
- Lightweight, self-contained single PHP file plugin

## Installation

1. Upload the `affiliatedealtracker.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress admin dashboard.
3. Navigate to **Affiliate Deals** menu in admin to enter your deals as JSON.

## Setup

Your deals must be entered in JSON format. Each deal requires the following fields:

- `title`: The deal title
- `description`: Short description of the deal
- `affiliate_url`: Your affiliate link to the offer
- `expiration_date`: Expiry date in `YYYY-MM-DD` format to auto-hide expired deals
- Optional `coupon_code`: Coupon code customers can use

Example JSON format:


{
  "deals": [
    {
      "title": "20% Off Product",
      "description": "Save 20% with this exclusive offer",
      "affiliate_url": "https://affiliate.example.com/?ref=123",
      "expiration_date": "2025-12-31",
      "coupon_code": "SAVE20"
    }
  ]
}


## Usage

To display the active affiliate coupons and deals on any post or page, add the shortcode:


[affiliate_deals]


The plugin will automatically show only deals not expired as of the current date.

---

This plugin is ideal for affiliate marketers seeking a simple way to aggregate and showcase affiliate deals effectively, increasing click-throughs and conversions.

For detailed help or customization, consider upgrading to premium add-ons with features like advanced scheduling, multiple affiliate network integrations, and analytics tracking.
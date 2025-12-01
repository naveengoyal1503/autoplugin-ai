# WP Revenue Tracker

Track and optimize your WordPress site's monetization efforts with detailed analytics, conversion tracking, and revenue insights.

## Features
- Track page views and conversions
- View revenue analytics in the WordPress admin
- Easy integration with affiliate links, ads, and digital products
- Detailed reporting for each page

## Installation
1. Upload the `wp-revenue-tracker.php` file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the 'Revenue Tracker' menu in your admin dashboard to view analytics

## Setup
- No additional setup required for basic tracking
- To track conversions, use the following JavaScript:
  javascript
  jQuery.post(ajaxurl, {
    action: 'track_conversion',
    page: window.location.pathname,
    revenue: 10.99 // Replace with actual revenue
  });
  

## Usage
- View revenue and conversion data in the admin dashboard
- Use the conversion tracking code on thank you pages or after successful transactions
- Optimize your monetization strategy based on real data

## Support
For support, please contact support@example.com
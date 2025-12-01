# Revenue Optimizer Pro

An all-in-one WordPress monetization management plugin that consolidates multiple revenue streams into a single, easy-to-use dashboard.

## Features

- **Unified Dashboard**: Track all revenue sources in one place
- **Revenue Logging System**: Automatically record and categorize income
- **Multiple Monetization Methods**: Support for ads, affiliate marketing, memberships, and sponsored content
- **Monthly Revenue Tracking**: Monitor performance month-over-month
- **Revenue Analytics**: Visualize income by source type
- **Customizable Settings**: Configure currency and preferences
- **Revenue Tracker Widget**: Display total revenue on any page using shortcodes
- **Freemium Model**: Free core features with premium upgrades available

## Installation

1. Download the plugin files
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin from the WordPress admin panel
4. Navigate to **Revenue Optimizer** in the main menu

## Setup

1. Go to **Revenue Optimizer > Settings**
2. Select your preferred currency (USD, EUR, or GBP)
3. Save your settings
4. Start configuring your monetization methods under **Monetization Methods**

## Usage

### Dashboard
The main dashboard displays:
- Total accumulated revenue
- Current month revenue
- Revenue breakdown by source

### Tracking Revenue
Revenue entries are logged in the database and displayed in the **Revenue Logs** section with details about type, amount, source, and date.

### Display Widget
Add the revenue tracker to any page or post using:

[revenue_tracker]


### Monetization Methods
Configure different revenue streams:
- Display Ads (AdSense integration)
- Affiliate Marketing links
- Membership subscriptions
- Sponsored content partnerships

## API for Logging Revenue

Developers can log revenue programmatically:

php
global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'rop_revenue_logs',
    array(
        'revenue_type' => 'affiliate_marketing',
        'amount' => 45.50,
        'source' => 'Product X Review',
        'date_logged' => current_time('mysql')
    )
);


## Frequently Asked Questions

**Q: Does this plugin slow down my site?**
A: No, Revenue Optimizer Pro is optimized for performance with minimal database overhead.

**Q: Can I export revenue data?**
A: The premium version includes CSV export functionality.

**Q: Is this plugin compatible with WooCommerce?**
A: Yes, it can track revenue from WooCommerce sales as a revenue source.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## Support

For support and feature requests, visit our website or contact our support team.

## License

GPL v2 or later
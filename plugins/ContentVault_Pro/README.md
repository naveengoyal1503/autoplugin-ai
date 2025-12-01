# ContentVault Pro - WordPress Monetization Plugin

## Overview

ContentVault Pro is a powerful WordPress plugin that enables site owners to monetize individual posts and pages through flexible paywalls and micro-transactions. Transform your content into a revenue stream without the complexity of traditional membership systems.

## Features

- **Individual Post/Page Paywalls**: Set custom prices for specific content pieces
- **One-Click Activation**: Enable paywalls directly from the post editor
- **Flexible Pricing**: Set your own price point for each piece of content
- **Transaction Dashboard**: Track all sales and revenue in real-time
- **Customizable Messages**: Personalize paywall messaging for your audience
- **Guest Purchase Support**: Allow both logged-in users and guests to purchase
- **Multiple Currency Support**: Accept payments in USD, EUR, and GBP
- **AJAX Checkout**: Fast, seamless payment processing without page reloads
- **Admin Analytics**: View monthly revenue, total transactions, and earnings trends
- **Shortcode Integration**: Use `[contentvault price="9.99" message="Unlock exclusive content"]` anywhere

## Installation

1. Download the ContentVault Pro plugin files
2. Upload the plugin folder to `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin dashboard
4. Navigate to ContentVault Pro → Settings to configure your preferences
5. Create database tables by visiting the Dashboard

## Setup

### Initial Configuration

1. Go to **ContentVault Pro → Settings**
2. Choose your preferred currency (USD, EUR, GBP)
3. Set minimum price threshold (default: $0.99)
4. Toggle guest purchase option if desired
5. Customize success message for transactions
6. Click **Save Settings**

### Enable Paywall on Posts

1. Edit any post or create a new one
2. Scroll to the **ContentVault Pro Settings** meta box
3. Check "Enable Paywall for this post"
4. Enter the price in USD
5. Add a custom paywall message (optional)
6. Publish or update the post

## Usage

### For Content Creators

**Setting Paywalls**: Simply check the paywall option in the post editor and set your price. Your content preview (first 50 words) will display before the paywall.

**Viewing Analytics**: Access the Dashboard to monitor total revenue, monthly earnings, and transaction count. The Transactions page shows detailed purchase history.

### For Site Visitors

When accessing paywalled content, visitors see:
- A content preview (excerpt)
- Your custom paywall message
- The price in their configured currency
- An "Unlock Content" button for immediate purchase

### Using Shortcodes

Add paywalls anywhere using the shortcode:


[contentvault price="14.99" message="Premium tutorial unlock" button_text="Get Access"]


**Parameters**:
- `price` (required): Price in dollars
- `message`: Custom paywall message
- `button_text`: Button label

## Transaction Tracking

All purchases are stored in the wp_cv_transactions table with:
- Unique transaction ID
- User and post information
- Purchase amount and currency
- Transaction status (completed/pending)
- Timestamp

Access this data via ContentVault Pro → Transactions in the admin.

## Pricing & Monetization Model

- **License**: $99/year annual subscription (auto-renews)
- **Transaction Fee**: 2% of each sale
- **Example**: $10 sale = $0.20 transaction fee

This recurring revenue model ensures continuous plugin updates, customer support, and feature development.

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+
- jQuery

## Support

For issues, feature requests, or assistance, visit the ContentVault Pro support portal or email support@contentvault.local

## License

GPL v2 or later - See LICENSE file for details

## Changelog

### Version 1.0.0
- Initial release
- Core paywall functionality
- Admin dashboard and analytics
- Transaction tracking
- Multi-currency support
- Shortcode integration
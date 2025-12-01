# CommissionFlow - Affiliate Commission Management Plugin

## Overview

CommissionFlow is a powerful WordPress plugin that automates affiliate commission tracking, management, and payouts. Perfect for WordPress site owners, digital product creators, and e-commerce businesses looking to scale through an affiliate program.

## Features

### Core Features (Free)
- **Affiliate Management**: Create and manage up to 10 affiliates with unique tracking codes
- **Commission Tracking**: Automatically track sales and calculate commissions
- **Click Analytics**: Monitor affiliate traffic and clicks in real-time
- **Commission Dashboard**: View pending commissions, active affiliates, and click stats
- **Shortcode Integration**: Easily embed affiliate links using `[cf_affiliate_link]` shortcode

### Premium Features ($29/month)
- Unlimited affiliate accounts
- Advanced analytics and reporting
- Automated monthly payouts via PayPal or bank transfer
- Custom commission rules per affiliate
- Email notifications for conversions
- Multi-tier affiliate programs
- CSV export functionality

## Installation

1. Download the CommissionFlow plugin ZIP file
2. Go to WordPress Admin Dashboard → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate Plugin"
5. Navigate to the CommissionFlow menu in the left sidebar

## Setup

1. **Create Affiliates**: Go to CommissionFlow → Affiliates and add your first affiliate
2. **Set Commission Rates**: Assign commission percentages (default 10%)
3. **Generate Tracking Codes**: Each affiliate receives a unique code automatically
4. **Add Affiliate Links**: Use the shortcode `[cf_affiliate_link affiliate_code="CODE" text="Join Our Program"]` on your pages

## Usage

### For Site Administrators

**View Dashboard**
- Navigate to CommissionFlow dashboard to see key metrics
- Monitor active affiliates, pending commissions, and total clicks

**Manage Affiliates**
- Create new affiliate accounts and assign commission rates
- Approve or suspend affiliates as needed
- Track affiliate performance and status

**Monitor Commissions**
- View all commission transactions
- See pending and completed payouts
- Filter by date, affiliate, or status

### For Affiliates

**Promote Your Products**
- Share your unique affiliate link with your audience
- Earn commission on every sale generated through your link
- Track your performance in real-time

**Get Paid**
- Earn commissions automatically when customers purchase
- Payments processed monthly (Premium feature)
- Withdraw earnings to PayPal or bank account

## Shortcode Examples


[cf_affiliate_link affiliate_code="JOHN123" text="Get Premium Access"]

[cf_affiliate_link affiliate_code="JANE456" text="Start Your Free Trial"]


## Technical Details

### Database Tables

The plugin creates three main tables:
- `wp_cf_affiliates` - Stores affiliate information and commission rates
- `wp_cf_commissions` - Records all sales and commission transactions
- `wp_cf_clicks` - Tracks affiliate link clicks for analytics

### REST API

Access affiliate data via REST API:

GET /wp-json/commissionflow/v1/affiliates


## Support

For support, documentation, and premium upgrades, visit https://commissionflow.io

## License

GPL v2 or later
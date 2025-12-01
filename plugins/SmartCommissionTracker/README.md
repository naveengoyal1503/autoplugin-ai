# SmartCommissionTracker

Automate affiliate commission tracking, payout management, and performance analytics for WordPress sites.

## Features

- **Automated Commission Tracking**: Automatically log and track affiliate commissions from sales
- **Affiliate Management**: Create, manage, and monitor affiliate accounts with customizable commission rates
- **Dashboard Analytics**: View real-time metrics including total affiliates, pending payouts, and paid commissions
- **Affiliate Portal**: Allow affiliates to view their earnings through a frontend dashboard
- **REST API**: Log commissions programmatically via secure API endpoints
- **Payout Management**: Track pending and paid commissions with detailed transaction history
- **Freemium Model**: Free tier with core features; premium tier ($9.99/month) includes advanced analytics and automated payouts

## Installation

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins > Add New
4. Click "Upload Plugin" and select the ZIP file
5. Click "Install Now" then "Activate Plugin"

## Setup

### Initial Configuration

1. Go to **Commission Tracker > Settings** in the WordPress admin
2. Set your default commission rate (percentage)
3. Set your payout threshold (minimum amount before automatic payout)
4. Save settings

### Adding Affiliates

1. Navigate to **Commission Tracker > Affiliates**
2. Create new affiliate accounts by adding existing WordPress users
3. Customize individual commission rates as needed

### Adding Affiliate Dashboard

Add the affiliate dashboard to any page using the shortcode:


[affiliate_dashboard]


This displays pending and paid commissions to logged-in affiliates.

## Usage

### Logging Commissions via API

Make a POST request to:

/wp-json/sct/v1/commission


With JSON body:

{
  "affiliate_id": 1,
  "sale_amount": 100.00,
  "transaction_id": "txn_12345"
}


Include authorization header (admin user or API key)

### Admin Dashboard

Access the main dashboard at **Commission Tracker** to view:
- Total number of active affiliates
- Pending commissions awaiting payout
- Total commissions already paid

## Premium Features (Coming Soon)

- Automated weekly/monthly payouts
- Advanced analytics and reporting
- Commission performance by traffic source
- Email notifications for affiliates
- Multi-tier commission structures

## Support

For support, visit our documentation or contact support@smartcommissiontracker.com
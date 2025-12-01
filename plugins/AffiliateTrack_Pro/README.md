# AffiliateTrack Pro

Advanced affiliate link tracking and performance analytics plugin for WordPress with built-in commission management and automated reporting.

## Features

- **Link Tracking**: Track clicks and conversions for unlimited affiliate links
- **Performance Analytics**: Real-time dashboard showing clicks, conversions, and revenue metrics
- **Commission Management**: Set custom commission rates for each affiliate link
- **Click Attribution**: Capture IP address and user agent data for each click
- **Shortcode Support**: Easy integration with [affiliate_link code="YOUR_CODE" text="Link Text"] shortcode
- **Revenue Tracking**: Monitor earnings from each affiliate link
- **Database Optimization**: Efficient MySQL queries for fast performance
- **Freemium Model**: Free tier with basic features, premium tier for unlimited links and advanced analytics

## Installation

1. Download the plugin files
2. Upload the plugin folder to `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin dashboard
4. Navigate to AffiliateTrack Pro menu in the admin sidebar

## Setup

1. Go to **AffiliateTrack Pro > Settings**
2. Enable click tracking by checking the "Enable Click Tracking" option
3. Click Save Changes

## Usage

### Creating Affiliate Links

1. Navigate to **AffiliateTrack Pro > Manage Links**
2. Fill in the form with:
   - **Link Name**: A descriptive name (e.g., "Amazon Product Review")
   - **Target URL**: The actual URL you want to redirect to
   - **Affiliate Code**: A unique identifier for this link (e.g., "amazon-book-001")
   - **Commission Rate**: Your expected commission percentage
3. Click "Add Link"

### Using Affiliate Links in Posts

Add the shortcode to any post or page:


[affiliate_link code="amazon-book-001" text="Check out this book on Amazon"]


Replace `amazon-book-001` with your affiliate code and customize the link text.

### Viewing Analytics

1. Go to **AffiliateTrack Pro > Dashboard** to see overall performance metrics
2. View detailed statistics for each link in **Manage Links** section
3. Track total clicks, conversions, and revenue across all links

## Monetization

AffiliateTrack Pro offers a freemium model:

- **Free**: Track up to 10 affiliate links with basic click analytics
- **Premium ($9.99/month)**: Unlimited affiliate links, advanced analytics dashboard, automated commission payouts, and monthly performance reports

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
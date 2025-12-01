# ContentVault Premium

A powerful WordPress plugin for creating and managing membership sites with tiered subscription plans, content gating, and recurring payments.

## Features

- **Tiered Membership Plans**: Create multiple subscription tiers with different price points and features
- **Content Gating**: Restrict blog posts and pages to premium members only
- **Recurring Billing**: Automated monthly and yearly subscription management
- **Multiple Payment Gateways**: Support for Stripe and PayPal payments
- **Member Dashboard**: Users can manage their subscriptions and view their membership status
- **Email Integration**: Built-in integration with Mailchimp, ConvertKit, and Constant Contact
- **Analytics**: Track subscription metrics and revenue
- **Community Features**: Private member forums and live chat support areas
- **Flexible Pricing**: Offer multiple price points and service levels

## Installation

1. Upload the `contentvault-premium` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin 'Plugins' menu
3. Navigate to ContentVault > Settings to configure payment methods

## Setup

### Step 1: Configure Payment Gateway

1. Go to **ContentVault > Settings**
2. Select your preferred payment gateway (Stripe or PayPal)
3. Enter your API credentials
4. Save settings

### Step 2: Create Membership Plans

1. Navigate to **ContentVault > Plans**
2. Click "Add New Plan"
3. Enter plan details:
   - Plan Name (e.g., "Pro", "Elite")
   - Price
   - Billing Cycle (Monthly, Yearly)
   - Features description
4. Save the plan

### Step 3: Gate Content

1. Edit a post or page
2. Check the "Premium Content" checkbox
3. Publish or update
4. Only premium members can access this content

## Usage

### Shortcodes

Insert `[contentvault_subscribe]` on any page to display a subscription button.

### Member Dashboard

Members can access their dashboard to:
- View active subscriptions
- Manage billing information
- Download resources
- Access community forums

### Email Marketing Integration

Connect your email marketing platform:
1. Go to Settings
2. Enter your email service API key
3. Automatically sync subscribers

## Pricing Models

- **Free Version**: Basic content gating
- **Premium Version**: $99/year for advanced features including priority support, advanced analytics, and customization options

## Monetization

ContentVault Premium uses a freemium model. The free version includes basic membership functionality, while the premium version ($99/year) unlocks:
- Advanced member segmentation
- Drip content features
- Priority email support
- Custom plugin modifications
- Monthly analytics reports

## Support

For support, visit our documentation or contact support@contentvault-premium.com

## License

GPL v2 or later
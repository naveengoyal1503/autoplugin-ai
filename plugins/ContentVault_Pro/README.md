# ContentVault Pro

A comprehensive membership and digital product management plugin for WordPress monetization. Easily manage premium content, sell digital products, and build sustainable recurring revenue through flexible subscription tiers.

## Features

- **Membership Management**: Create tiered membership plans with automatic renewals
- **Digital Product Sales**: Sell eBooks, courses, templates, and downloadable files
- **Content Restrictions**: Restrict post and page access to members or specific membership tiers
- **Paywall System**: Automatically display paywalls for restricted content
- **Member Dashboard**: Track active members, revenue, and purchase history
- **Stripe Integration**: Accept payments securely through Stripe
- **Shortcodes**: Easy-to-use shortcodes for displaying products and member content
- **User-Friendly Admin**: Intuitive admin interface for managing products and members

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/contentvault-pro/` directory
3. Activate the plugin through WordPress admin dashboard
4. Navigate to ContentVault Pro → Settings to configure

## Setup & Configuration

### Step 1: Get Stripe Keys

1. Sign up for a Stripe account at stripe.com
2. Go to API Keys section in your Stripe Dashboard
3. Copy your Publishable Key and Secret Key

### Step 2: Configure Plugin Settings

1. Go to ContentVault Pro → Settings
2. Enter your Stripe Publishable Key and Secret Key
3. Save changes

### Step 3: Create Membership Tiers

Define your membership tiers (e.g., Basic, Premium, VIP) with associated permissions and pricing.

### Step 4: Create Digital Products

1. Go to Digital Products
2. Click "Add New"
3. Set product title, description, price, and download file URL
4. Publish

## Usage

### Display Digital Products

Use the shortcode to display all available products:


[contentvault_products]


### Restrict Post Content

1. Create or edit a post
2. Scroll to "Content Restrictions" meta box
3. Select "Members Only" or "Specific Tiers"
4. Save post

### Display Member-Only Content

Wrap content in shortcode:


[contentvault_member_content]
This content is only for premium members
[/contentvault_member_content]


### Add Paywall

Manually insert paywall shortcode:


[contentvault_paywall post_id="123"]


## Monetization Models Supported

- **Freemium**: Offer free content with premium upgrades
- **Tiered Memberships**: Multiple subscription levels
- **Digital Products**: One-time purchases
- **Hybrid**: Combine memberships with product sales

## Dashboard Metrics

The main ContentVault Pro dashboard displays:

- Total Active Members
- Total Revenue Generated
- Recent Transactions
- Member Activity

## Frequently Asked Questions

**Q: How do users sign up for memberships?**
A: Users can sign up through membership signup buttons that trigger Stripe checkout.

**Q: Can I offer trial periods?**
A: Yes, configure trial periods in your Stripe account for recurring subscriptions.

**Q: Is there a license system?**
A: The plugin tracks purchases per user. Digital product licenses are tied to user accounts.

**Q: Can I restrict content to multiple membership tiers?**
A: Yes, specify multiple comma-separated tier names in the Content Restrictions meta box.

## Support

For issues or questions, contact support through the plugin documentation.

## License

GPL v2 or later
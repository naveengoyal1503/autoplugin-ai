# ContentVault Pro

A powerful WordPress membership and subscription plugin designed to help content creators, educators, and site owners monetize their content through tiered membership plans and recurring revenue models.

## Features

### Core Membership Functionality
- **Tiered Membership Plans**: Create multiple subscription tiers with custom pricing and billing cycles
- **Flexible Billing Cycles**: Support for monthly, quarterly, annual, and custom billing intervals
- **Stripe Integration**: Seamless payment processing with industry-leading security
- **Recurring Payments**: Automated recurring billing for predictable revenue streams
- **Member Management**: Comprehensive dashboard to manage active members and subscriptions

### Content Protection
- **Post-Level Protection**: Mark individual posts or pages as exclusive to specific membership tiers
- **Content Restrictions**: Easy shortcode system to protect any content on your site
- **Graceful Fallbacks**: Show compelling upgrade prompts instead of hiding content completely
- **Multi-Tier Access**: Grant access based on membership level

### Revenue Analytics
- **Real-Time Dashboard**: Track total revenue, active members, and key metrics at a glance
- **Transaction History**: Complete record of all payments and refunds
- **Member Lifetime Value**: Understand the long-term profitability of each member
- **Revenue Reports**: Detailed analytics to inform business decisions

### User Experience
- **Shortcode-Based Logins**: `[contentvault_login]` for member authentication
- **Registration Forms**: `[contentvault_register]` for new member signups
- **Protected Content Blocks**: `[contentvault_protected]` to restrict specific sections
- **Member Communities**: Support for member-only forums and discussion areas (via integration)
- **Responsive Design**: Mobile-friendly interface for all devices

### Member Retention
- **Automated Renewals**: Reduce churn with automatic payment processing
- **Subscriber Notifications**: Email reminders for upcoming renewals
- **Pause/Resume Functionality**: Allow members to temporarily pause subscriptions
- **Flexible Cancellation**: Easy cancellation options to maintain trust and compliance

## Installation

1. Download the ContentVault Pro plugin from the WordPress plugin repository
2. In your WordPress admin dashboard, go to **Plugins → Add New → Upload Plugin**
3. Select the ContentVault Pro zip file and click **Install Now**
4. Click **Activate Plugin**
5. Navigate to **ContentVault → Settings** to begin configuration

## Setup Instructions

### Step 1: Configure Payment Gateway
1. Go to **ContentVault → Settings**
2. Enter your Stripe Public Key and Secret Key
3. Select your preferred payment currency (USD, EUR, GBP, AUD)
4. Enter your Site Name
5. Click **Save Settings**

### Step 2: Create Membership Plans
1. Go to **ContentVault → Plans**
2. Click **Add New Plan**
3. Enter plan details:
   - Plan Name (e.g., "Basic", "Pro", "Premium")
   - Monthly/Annual Price
   - Billing Cycle
   - Description and features
4. Save the plan
5. Repeat for additional tiers

### Step 3: Protect Your Content
1. When creating or editing a post/page, check **"Restrict to Premium Members"**
2. Select which membership plan(s) should have access
3. Publish or update the post

### Step 4: Add Registration & Login Pages
1. Create a new page called "Join" or "Subscribe"
2. Add the shortcode: `[contentvault_register]`
3. Create another page called "Member Login"
4. Add the shortcode: `[contentvault_login]`
5. Publish both pages

## Usage

### For Site Owners

**View Member Analytics**
- Dashboard displays total revenue, active members, total plans, and average member lifetime value
- Access detailed member information in the Members section
- Monitor transaction history for all recurring payments

**Manage Plans**
- Edit or delete existing plans
- Track member count per plan
- Adjust pricing as needed

**Monitor Members**
- See real-time list of active, paused, and canceled subscriptions
- View join dates and next billing dates
- Identify high-value members

### For Visitors

**Register for Membership**
1. Navigate to registration page
2. Enter email, name, and password
3. Select desired membership tier
4. Complete Stripe payment
5. Instant access to exclusive content

**Access Exclusive Content**
- Premium members can view protected posts immediately
- Non-members see teaser content with call-to-action to upgrade
- Members can view their subscription status and upcoming renewal date

## Shortcodes

### `[contentvault_login]`
Displays a member login form. Members enter their email and password to access their account.

### `[contentvault_register]`
Shows a registration form with fields for name, email, password, and plan selection. Includes integrated payment processing.

### `[contentvault_protected]`
Protects specific content sections. Only members with active subscriptions can view protected blocks.

## Frequently Asked Questions

**Q: Can I offer multiple membership tiers?**
A: Yes! Create unlimited tiered plans with different features, pricing, and access levels.

**Q: How are payments processed?**
A: ContentVault Pro uses Stripe for secure payment processing. All transactions are encrypted and PCI-compliant.

**Q: What billing intervals are supported?**
A: Monthly, quarterly, annual, and custom intervals are all supported.

**Q: Can members pause or cancel their subscription?**
A: Yes, members can manage their subscription status from their account dashboard. They can pause temporarily or cancel anytime.

**Q: Does the plugin work with my existing content?**
A: Yes, you can protect any existing posts or pages by marking them as restricted.

**Q: What happens when a payment fails?**
A: The plugin automatically retries failed payments and notifies members via email.

## Support

For additional help, visit our documentation at contentvault.pro or contact support@contentvault.pro.

## License

ContentVault Pro is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Full membership and subscription functionality
- Stripe integration
- Revenue analytics dashboard
- Content protection system
- Member management tools
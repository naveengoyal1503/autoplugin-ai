# Smart Donation Pro

## Description
Boost your WordPress site's income with **Smart Donation Pro**, a lightweight plugin that adds eye-catching donation buttons and progress bars. Perfect for bloggers, creators, and non-profits. Free version uses PayPal; Pro adds Stripe, analytics, and more.

## Features
- **Customizable donation shortcode**: `[smart_donation goal="100" current="25" button_text="Support Us"]`
- Visual progress bars showing goal progress
- PayPal integration (button ID required)
- Mobile-responsive design
- **Pro only**: Stripe payments, recurring donations, donation tracking dashboard, custom CSS/JS, A/B testing

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`
2. Activate via **Plugins > Installed Plugins**
3. Go to **Settings > Donation Pro** to configure PayPal email and button ID
4. Create a PayPal donate button at [paypal.com/buttons](https://www.paypal.com/buttons) and copy the Button ID
5. Add shortcode to any post/page: `[smart_donation]`

## Setup
1. In **Settings > Donation Pro**:
   - Enter your PayPal email
   - Paste your PayPal Button ID
2. Customize shortcode attributes:
   - `goal`: Donation target (e.g., "500")
   - `current`: Amount raised (e.g., "150")
   - `amount`: Default donation amount (e.g., "10")

## Usage
- Embed shortcode in posts, pages, sidebars, or widgets
- Update `current` manually or via Pro dashboard
- Example: `[smart_donation goal="1000" current="450" button_text="Buy Me Coffee" amount="5"]`

## Pro Upgrade
Unlock premium features for $29/year:
- Stripe integration
- Automatic progress tracking
- Exportable donation reports
- Unlimited custom designs

## Support
Report issues on WordPress.org forums. Pro users get priority email support.
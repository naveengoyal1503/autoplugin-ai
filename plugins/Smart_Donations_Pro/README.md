# Smart Donations Pro

## Features
- **Easy Donation Buttons**: Add customizable donation shortcodes to any page or post[3].
- **Progress Bars**: Visual goal tracking to encourage more donations (e.g., "$250 of $1000 raised")[1].
- **PayPal Integration**: One-click donations via hosted PayPal buttons.
- **Freemium Ready**: Core free; premium unlocks Stripe, recurring subs, analytics ($29/year)[5].
- **Mobile Responsive**: Works on all devices.
- **Admin Dashboard**: Simple settings for PayPal email and button ID.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Smart Donations** to enter your PayPal email.
4. Create a PayPal hosted button at [paypal.com/buttons](https://www.paypal.com/buttons/) and note the Button ID.

## Setup
1. In **Settings > Smart Donations**, paste your PayPal email.
2. Use shortcode: `[smart_donation amount="5" label="Buy Me Coffee" goal="1000" current="250" paypal_email="your@email.com"]`.
3. Customize amounts, labels, goals dynamically.

## Usage
- Embed shortcode in posts/pages: Adjust `amount`, `goal`, `current` for real-time tracking.
- Track donations via PayPal dashboard.
- **Pro Tip**: Update `current` manually or via premium AJAX updates for live progress[1][3].
- Example: `[smart_donation amount="10" label="Support Us"]`. 

**Upgrade to Pro** for recurring donations (65% better retention) and analytics[5].
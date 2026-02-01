# Smart Donation Booster

## Features
- **Smart Popups**: Auto-show customizable donation popups after 10 seconds with smooth animations.
- **Progress Bars**: Visual goal tracker to motivate donors (e.g., "$500/$1000 reached").[1][3][5]
- **Quick Amounts**: One-click buttons for common amounts like $5, $10, $20.
- **PayPal Integration**: Secure payments via PayPal (no account needed for users).[3]
- **Shortcodes**: Embed buttons anywhere with `[sdb_donation amount="10"]`.
- **Admin Dashboard**: Easy settings for title, message, goals, and PayPal email.
- **Mobile-Responsive**: Works on all devices.
- **Freemium Model**: Pro adds A/B testing, geo-targeting, analytics ($29/year).[2][5]

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Booster** to configure.
4. Add your PayPal email and set goal.

## Setup
1. **PayPal Account**: Get a business account at paypal.com.
2. **Configure Settings**:
   - Enable popup.
   - Set title/message.
   - Define goal/current amount.
   - Add quick amounts (e.g., `5,10,20,50`).
   - Enter PayPal email.
3. Save changes. Test popup on frontend.

## Usage
- **Popup**: Auto-triggers on all pages (delay: 10s).
- **Shortcode**: `[sdb_donation amount="20"]` for inline buttons.
- **Track Progress**: Manually update "Current Amount" after donations.
- **Customize CSS/JS**: Override via theme or pro version.

## Pro Version
- A/B test messages.
- Geo/IP targeting.
- Donation analytics.
- Unlimited goals/campaigns.
- Priority support.

**Upgrade**: Visit [pro link] for $29/year.

## FAQ
- **Slow site?** Lightweight, no external deps beyond jQuery.
- **Other payments?** Pro supports Stripe.

## Changelog
**1.0.0**: Initial release with core features.
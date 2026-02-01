# Smart Donation Pro

## Features

- **One-Click Donation Buttons**: Easy shortcode `[sdp_donate]` to add customizable donation forms anywhere on your site.
- **Tiered Giving Levels**: Pre-set amounts (e.g., $5, $10, $25) or custom inputs for flexible donations[1][3].
- **PayPal Integration**: Secure, no-account payments via PayPal (email setup in admin).
- **Donation Analytics**: Track recent donations in the WordPress admin dashboard.
- **Mobile-Responsive Design**: Clean, professional UI that works on all devices.
- **Freemium Ready**: Free core; premium unlocks Stripe, email receipts, and custom branding.

Boost conversions with tips like pricing psychology (e.g., $9.99 tiers) and freemium strategies[1][5].

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to enter your PayPal email.
4. Add `[sdp_donate tiers="5,10,25,50,100"]` to any post/page.

## Setup

1. **Configure PayPal**: In admin settings, add your verified PayPal business email.
2. **Customize Shortcode**: Use attributes like `amount="10"`, `button_text="Buy Me Coffee"`, `tiers="9.99,19.99,49.99"`.
3. **Test**: Visit the page, select amount, and confirm PayPal redirect.

## Usage

- **Shortcode Examples**:
  - Basic: `[sdp_donate]`
  - Custom: `[sdp_donate tiers="5,15,50" button_text="Support Us"]`
- **View Logs**: Admin dashboard shows last 50 donations with amounts/timestamps.
- **Monetization Tips**:
  - Place near content footers for tips/donations[3].
  - Offer tiers for higher averages (subscriptions retain 65% more users)[5].
  - Upsell premium via plugin updates.

## FAQ

**Does it slow my site?** No, lightweight with inline assets and no external dependencies.

**Supported Payments?** PayPal donations (premium: Stripe). Returns user to `/thank-you/` page.

**Privacy?** Logs are site-only; no data shared.

For premium support: Visit example.com/premium.
# Smart Donation Pro

A user-friendly WordPress plugin to add customizable donation forms to your site, perfect for monetizing blogs, non-profits, or content creators with PayPal integration.

## Features
- **Tiered Donations**: Predefined amounts like "Coffee ($5)", "Lunch ($20)" with customizable tiers via admin.
- **Custom Amounts**: Allow visitors to enter their own donation value.
- **PayPal Integration**: One-click redirects to PayPal for secure payments[1][3].
- **Shortcode Support**: Use `[smart_donation]` anywhere on your site.
- **Admin Dashboard**: Easy settings for PayPal email, tiers (JSON), and messages.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Analytics Ready**: Track donations via PayPal dashboard (premium adds site-side stats).

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Smart Donation Pro** to configure PayPal email and tiers.

## Setup
1. In admin settings:
   - Enter your PayPal email.
   - Edit tiers as JSON array, e.g., `[["Coffee",5],["Dinner",50]]`.
   - Enable custom amounts and set thank-you message.
2. Save settings.
3. Add `[smart_donation]` to any post/page/widget.

## Usage
- Embed the shortcode on pages like sidebar, footer, or dedicated "Support" page.
- Visitors select tier or custom amount and click "Donate via PayPal".
- Redirects to PayPal; returns to your site post-payment.
- **Pro Tip**: Use pricing psychology like $4.99 tiers to boost conversions by up to 28%[5].

## Freemium Upsell
Upgrade to Pro for recurring donations, Stripe support, email receipts, and conversion analytics ($49/year).

## Support
Report issues on WordPress.org forums. Compatible with latest WordPress.
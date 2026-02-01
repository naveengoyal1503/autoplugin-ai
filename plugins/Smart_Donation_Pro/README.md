# Smart Donation Pro

A lightweight, self-contained WordPress plugin for collecting donations via shortcode. Supports tiered amounts, email capture, database analytics, and PayPal integration. Freemium model with pro upgrades.

## Features

- **Easy Shortcode**: `[smart_donation]` to embed donation form anywhere.
- **Customizable Tiers**: Define amounts like `[smart_donation tiers="5,10,25,50,100"]`.
- **Donor Tracking**: Stores donations in DB with email and timestamp.
- **Admin Analytics**: View all donations in Tools > Donations.
- **PayPal Ready**: Add your hosted button ID for instant PayPal.
- **Stripe Compatible**: Pro version includes Stripe (PK key support).
- **Mobile Responsive**: Clean, modern design.
- **Secure**: Nonce-protected AJAX submissions.

**Pro Features (Upsell)**: Recurring Stripe, custom branding, email notifications, export CSV, donation goals.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via Plugins > Installed Plugins.
3. Use shortcode `[smart_donation]` on any page/post.

## Setup

1. Go to **Settings > Donations** for analytics.
2. Customize: `[smart_donation title="Buy Me Coffee" tiers="3,5,10" paypal="YOUR_PAYPAL_BUTTON_ID"]`.
3. For PayPal: Create a hosted button at paypal.com and paste the ID.
4. Test form submission.

## Usage

- Embed on sidebar, posts, or dedicated page.
- View stats: **Tools > Donations** shows total donations, amounts, emails.
- Example tiers boost conversions with psychology pricing (e.g., $4.99).[1][6]

## FAQ

**Self-contained?** Yes, single PHP file with embedded JS logic.
**Monetization?** Free core; pro via add-on ($29/year).
**GDPR?** Emails optional; add privacy note.

## Changelog

**1.0.0** Initial release.

Support: example.com/support
# Smart Content Paywall

**Transform your WordPress site into a revenue machine by automatically paywalling content after a free preview.**

## Features

- **Automatic Paywalling**: Show only the first 100 words (customizable) for free, then prompt payment to unlock full article.
- **Stripe Integration**: One-click payments with Stripe (free version). Pro adds recurring subscriptions.
- **Post-Level Control**: Enable/disable per post via checkbox in post editor.
- **User Access Tracking**: Paying users get permanent access to purchased content.
- **Shortcode Support**: `[scp_paywall]` or `[scp_paywall id="123"]` for custom placement.
- **Mobile-Responsive**: Clean, modern paywall UI.

**Pro Features ($49/year)**: Subscription tiers, analytics dashboard, A/B testing, email integrations, unlimited paywalls.

## Installation

1. Upload the `smart-content-paywall` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Content Paywall** to configure Stripe keys and price.
4. Edit any post, check "Enable Paywall" metabox, and save.

## Setup

1. **Get Stripe Keys**: Sign up at [stripe.com](https://stripe.com), get Publishable and Secret keys from Dashboard > Developers.
2. **Configure Plugin**: Enter keys, set price (e.g., $4.99), preview words (e.g., 100).
3. **Enable on Posts**: In post editor, check the "Paywall this content" box.
4. **Test**: View post as logged-out user; paywall appears after preview.

**Pro Tip**: Use tiered pricing like $4.99 one-time or $9.99/month for recurring (Pro only).

## Usage

- **Automatic**: Add filter to all posts or enable per post.
- **Shortcode**: Place `[scp_paywall]` anywhere to protect wrapped content.
- **Admin Dashboard**: View settings under **Settings > Content Paywall**.
- **User Experience**: After payment, content unlocks instantly; logged-in users retain access.

## FAQ

**How do I upgrade to Pro?** Visit example.com/pro for subscription.

**Does it slow my site?** No, lightweight with no external calls unless paywall shown.

**Supported Payments?** Stripe cards (Visa/MC/Amex). Pro adds PayPal, Apple Pay.

## Changelog

**1.0.0** - Initial release with one-time payments.

**Support**: Contact support@example.com
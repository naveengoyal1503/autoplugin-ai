# AI Content Monetizer

A powerful WordPress plugin to monetize your AI-generated or premium content by locking it behind simple paywalls with one-time micropayments.

## Features
- **Automatic Content Locking**: Automatically locks full post content after a teaser on single posts.
- **Shortcode Support**: Use `[acm_lock price="1.99"]` to lock any content block.
- **Daily Unlocks**: Demo mode simulates payments with daily IP-based unlocks (premium integrates Stripe/PayPal).
- **Admin Settings**: Customize unlock price and enable/disable auto-locking.
- **Responsive Design**: Clean, mobile-friendly lock overlay.
- **Freemium Ready**: Free core; upsell premium for payments, analytics, A/B testing.

## Installation
1. Upload the `ai-content-monetizer` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > AI Content Monetizer** to set your unlock price.

## Setup
1. In **Settings > AI Content Monetizer**, set the default unlock price (e.g., $0.99).
2. Toggle auto-locking for posts (default: on).
3. For custom locks, add `[acm_lock price="2.49"]Your premium content here[/acm_lock]` in posts/pages.

## Usage
- **Auto-Lock**: Publish posts; teaser shows, full content locks behind paywall.
- **Manual Lock**: Embed shortcode anywhere for granular control.
- **Visitor Experience**: Click "Unlock Now" simulates payment (shows message), reveals content for the day.
- **Demo Mode**: Uses IP + date for free daily unlocks. Premium version processes real payments.
- **Tracking**: View unlocks in options (premium dashboard coming).

## Freemium Upsell
Upgrade to Pro for:
- Real payment gateways (Stripe, PayPal).
- Usage analytics and earnings reports.
- Unlimited shortcodes and custom designs.

## Support
Contact support@example.com or visit plugin URI for help.

**Version 1.0.0 | Compatible with WordPress 6.0+**
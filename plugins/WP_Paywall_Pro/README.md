# WP Paywall Pro

Monetize your WordPress content with flexible paywalls and payment options. Lock posts, pages, or custom content behind a paywall and accept payments directly on your site.

## Features
- Lock any content with a shortcode
- Set custom price, currency, and access duration
- Simple payment form (extendable with Stripe/PayPal)
- Track paid access per user
- Admin settings for payment gateway integration

## Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WP Paywall Pro to configure your payment gateway

## Setup
- Use the `[paywall price="10" currency="USD" access_days="7"]Your premium content here[/paywall]` shortcode to lock content
- Configure your payment gateway API key in the plugin settings

## Usage
- Place the shortcode around any content you want to monetize
- Users will see a pay button and form to unlock access
- Paid access is tracked per user and expires after the set duration

## Notes
- For production, integrate a real payment processor (Stripe, PayPal, etc.)
- Extend the plugin with premium add-ons for recurring payments, analytics, and more
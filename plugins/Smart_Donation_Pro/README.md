# Smart Donation Pro

## Description
**Smart Donation Pro** is a lightweight, self-contained WordPress plugin that enables site owners to easily add customizable donation buttons and progress bars. Supports Stripe for card payments and PayPal for instant donations. Perfect for bloggers, creators, and non-profits to monetize content.[1][3]

## Features
- **Donation Buttons**: One-click Stripe and PayPal integration.
- **Progress Bars**: Visual goal tracking (e.g., fundraising campaigns).[1]
- **Customizable**: Shortcode with amount, button text, goal/current params.
- **Admin Settings**: Configure API keys and default amounts securely.
- **Mobile-Responsive**: Clean, modern design.
- **Freemium Ready**: Premium unlocks recurring donations, analytics, custom themes.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Settings** to enter Stripe/PayPal keys.[3]
4. Use shortcode `[smart_donation amount="20" button_text="Support Us" goal="1000" current="450"]`.

## Setup
1. **Stripe**: Sign up at stripe.com, get Publishable/Secret keys from Dashboard > Developers.[2]
2. **PayPal**: Use your business email (donations via PayPal Donate link).[3]
3. Save settings; test on staging site.

## Usage
- Embed shortcode in posts/pages/widgets.
- Example: `[smart_donation]` uses default $10 amount.
- Progress auto-calculates from `current/goal` attributes.
- Stripe handles payments securely; server-side charge on AJAX submit.

## Premium Features (Coming Soon)
- Recurring subscriptions (monthly/yearly).[1][5]
- Donation analytics dashboard.
- Custom themes and email receipts.

## Support
Report issues on WordPress.org forums. Premium support via email.

## Changelog
**1.0.0** - Initial release.
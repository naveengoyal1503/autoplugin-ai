# Smart Donation Pro

A lightweight, self-contained WordPress plugin for easy donation collection with progress tracking, PayPal integration, and customizable forms. Perfect for bloggers, creators, and non-profits.[1][3]

## Features
- **Customizable donation forms** via shortcode `[smart_donation goal="1000" title="Support Us!"]`.
- **Progress bar** showing real-time donation goals.
- **PayPal integration** for one-time donations (Stripe in Pro).
- **Admin dashboard** for settings and total tracking.
- **Mobile-responsive** design.
- **Freemium**: Upgrade for recurring payments, analytics, and more.

## Installation
1. Download and upload the single PHP file to `/wp-content/plugins/smart-donation-pro/`.
2. Activate the plugin in WordPress Admin > Plugins.
3. Go to Settings > Donation Pro to set your PayPal email.
4. Add shortcode to any page/post: `[smart_donation]`. Customize with `goal`, `title`, `button_text`.

## Setup
- **PayPal**: Enter your PayPal email in settings. Donations redirect to PayPal.
- **Goals**: Set `goal` attribute (e.g., `goal="5000"` for $5,000 target).
- **Currency**: Defaults to `$`, editable via shortcode.

## Usage
1. Create a page: "Support My Work".
2. Insert `[smart_donation goal="1000" title="Help Reach Our Goal!" button_text="Contribute"]`.
3. Track total donations in settings page.
4. **Pro Upgrade**: Recurring subs, Stripe, email notifications ($29/year).[1][6]

## FAQ
- **Tracks totals?** Yes, persists across sessions.
- **GDPR compliant?** No personal data stored.

## Changelog
- 1.0.0: Initial release.

> Boost donations with progress visuals – proven to increase engagement![6]
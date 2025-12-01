# ContentLocker Pro

ContentLocker Pro lets you monetize premium content by locking it behind subscription or pay-per-post models. Easily create multiple pricing tiers and restrict access to exclusive articles, videos, or downloads.

## Features

- Lock any post or content via shortcode `[lock_content]...[/lock_content]`
- Subscription price configurable via admin options
- Simple, simulated payment processing via AJAX (ready for real gateway integration)
- User access metadata to track subscriptions
- Works with any post type and content
- Supports logged-in user management

## Installation

1. Upload the `contentlockerpro.php` file to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress Plugins menu.
3. Navigate to Settings > ContentLocker Pro to set your subscription price and PayPal email.

## Setup

- Set the subscription price in the admin settings.
- (Future) Connect real payment gateways for actual transactions.

## Usage

Wrap any content you want to protect with the shortcode:

[lock_content]
Your premium content here.
[/lock_content]


Visitors will be prompted to subscribe to access the content. Logged-in users who subscribe gain full access.

## Monetization

Offers a freemium base with potential to add premium payment gateways or additional features as paid add-ons, supporting recurring revenue.

---

ContentLocker Pro is ideal for bloggers, educators, and membership sites looking to generate stable, recurring income by monetizing exclusive content.
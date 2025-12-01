# ContentLocker Pro

ContentLocker Pro is a WordPress plugin that helps content creators monetize their premium content by locking it behind a customizable content locker. Users can unlock content via a simple pay-per-access mechanism.

## Features

- Easily lock any part of your post or page content using a shortcode `[contentlocker]...[/contentlocker]`
- Simple pay-to-unlock button with customizable text
- Basic built-in payment simulation for demo and testing
- Cookie-based content unlocking for session duration
- Admin settings for payment email and button text customization
- Lightweight and self-contained single-file plugin

## Installation

1. Download the `contentlocker-pro.php` plugin file.
2. Upload it to your WordPress site's `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Setup

1. Go to the WordPress admin menu -> ContentLocker Pro.
2. Enter your payment receiver email address (used for actual payment gateway integration in a real scenario).
3. Customize the locker button text if desired.
4. Save your settings.

## Usage

- Wrap any content you want to lock with the shortcode:
  
  [contentlocker]
  Your premium content here.
  [/contentlocker]
  
- Visitors will see a lock message and pay button.
- After 'payment', content will be unlocked for 24 hours via cookie.

## Notes

- This version includes a mock payment process. For production, integrate with real payment gateways (e.g., PayPal, Stripe).
- Consider enhanced security and user management for subscriptions and repeated access.
- The plugin is designed to be extended with pro features like analytics, multi-tier subscriptions, and integrations.

---

*This plugin is a foundation for monetizing premium content on WordPress sites using a freemium plus subscription model.*
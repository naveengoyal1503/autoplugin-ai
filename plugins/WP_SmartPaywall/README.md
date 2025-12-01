# WP SmartPaywall

WP SmartPaywall is a dynamic paywall plugin that intelligently unlocks premium content based on user engagement, subscription status, or micro-payments.

## Features
- Unlock content via subscription, micro-payment, or user engagement (e.g., social share)
- Easy-to-use shortcode for locking content
- Admin settings for configuring unlock methods and pricing
- AJAX-powered unlocking for seamless user experience

## Installation
1. Upload the `wp-smartpaywall` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > SmartPaywall to configure the unlock method and price

## Setup
- Use the `[smartpaywall]` shortcode to lock content:
  
  [smartpaywall method="micro" price="2.99"]Your premium content here[/smartpaywall]
  
- Available methods: `subscription`, `micro`, `engagement`

## Usage
- For subscription: Users must subscribe to unlock
- For micro-payment: Users pay a set price to unlock
- For engagement: Users unlock by sharing on social media

## Requirements
- WordPress 5.0 or higher
- PHP 7.0 or higher

## Support
For support, please visit https://example.com/support
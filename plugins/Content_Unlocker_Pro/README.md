# Content Unlocker Pro

## Description
Content Unlocker Pro enables site owners to create tiered membership content access with flexible paywalls and microtransaction payment support. It allows you to lock content for different membership levels and monetize your premium posts or digital products easily.

## Features
- Shortcode-based content restriction by membership level (free, silver, gold, platinum).
- Simple unlock button with AJAX purchase simulation.
- Upgrade membership level via integrated microtransactions (payment logic stubbed for demonstration).
- Front-end styling and messaging.
- Works with logged-in users only.

## Installation
1. Upload the plugin file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure users are registered and can log in to purchase content unlocking.

## Setup
Use the `[content_unlocker level="silver"]Your premium content[/content_unlocker]` shortcode to restrict content to a certain membership level.

## Usage
- Wrap content within the shortcode `[content_unlocker]...[/content_unlocker]` specifying the `level` attribute.
- When users without access visit a locked post, they see an unlock button.
- Clicking unlock simulates payment and grants access by upgrading their membership level.

Example:

[content_unlocker level="gold"]This content is for Gold members only.[/content_unlocker]


Users need to log in to use the purchase feature. The plugin currently does not integrate real payment gateways — this should be extended when deploying.

This plugin provides a foundation for subscription, paywall, or microtransaction models suitable for content creators, educators, and small businesses.
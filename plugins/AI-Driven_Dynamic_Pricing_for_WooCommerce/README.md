# AI-Driven Dynamic Pricing for WooCommerce

Automatically optimize your WooCommerce product prices based on real-time demand, inventory levels, and competitor pricing to maximize profits.

## Features

- Enable dynamic pricing on individual WooCommerce products
- Adjust prices automatically based on inventory stock levels
- Set minimum and maximum pricing bounds per product
- Framework for competitor price factor integration (extendable)
- Fully integrated into WooCommerce product edit screen

## Installation

1. Upload the plugin PHP file to your `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress Plugins menu.
3. Go to any WooCommerce product and enable `Enable Dynamic Pricing` in the Pricing tab.
4. Configure minimum and maximum price limits if desired.

## Setup

This plugin uses simple demand-based logic by default:

- Low stock increases price up to 20%
- Plentiful stock slightly decreases price

You can extend or customize the pricing factors by modifying the plugin code.

Competitor pricing adjustment is a placeholder for future extensions.

## Usage

- Edit products in WooCommerce admin.
- Check `Enable Dynamic Pricing` option.
- Enter optional minimum and maximum price limits.
- Prices on the store front-end will be adjusted dynamically based on stock levels automatically.

Upgrade to a Pro version (planned) for advanced AI pricing algorithms, competitor price scraping, and flash sale automation.
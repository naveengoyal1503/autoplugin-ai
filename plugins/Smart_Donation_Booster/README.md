# Smart Donation Booster

## Features
- **Eye-catching progress bars** with real-time updates via AJAX.
- **One-click donation buttons** for $5, $10, $25, or custom amounts.
- **PayPal integration** for instant payments (configure your email in settings).
- **Admin dashboard** to set goals, track current amount, and customize.
- **Shortcode support**: `[sdb_donation]` or `[sdb_donation goal="5000"]`.
- **Gamified experience** encourages repeat donations with visual progress.
- **Mobile-responsive** and lightweight, no performance impact.
- **Freemium-ready**: Extend with pro features like multiple goals, analytics.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Booster** to set your PayPal email and goal.
4. Add `[sdb_donation]` shortcode to any post, page, or widget.

## Setup
1. **Configure Settings**:
   - Goal Amount: e.g., 1000
   - Current Amount: Start at 0
   - PayPal Email: Your business PayPal email
2. **Customize Shortcode**:
   - `[sdb_donation goal="2000" current="500" paypal="your@email.com"]`
3. **PayPal Button**: Replace `hosted_button_id` in code with your own via PayPal Button Manager for production.

## Usage
- Embed shortcode in sidebar, posts, or footers.
- Visitors click buttons to donate; progress updates instantly.
- Track donations in admin; manually adjust current amount.
- **Pro Tip**: Place near high-traffic content for max conversions[1][3][5].

## Support
Report issues on WordPress.org forums. Premium support available.

## Changelog
**1.0.0**: Initial release with core donation features.
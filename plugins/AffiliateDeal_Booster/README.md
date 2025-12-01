# AffiliateDeal Booster

**AffiliateDeal Booster** is a powerful WordPress plugin designed to help affiliate marketers, bloggers, and ecommerce sites increase their commissions by automatically aggregating and displaying affiliate deals, coupons, and flash sales from multiple affiliate networks.

---

## Features

- Auto-fetch deals and coupons from multiple affiliate network APIs via JSON URLs
- Cache deals to improve site performance
- Display deals with title, discount, and expiry date
- Shortcode `[affiliate_deals]` to display deals anywhere on your site
- Admin settings page to add affiliate API endpoints
- Manual and scheduled automatic updates (hourly cron job)
- Clean, minimalistic design with no dependencies

---

## Installation

1. Download the `affiliate-deal-booster.php` file.
2. Upload it to your WordPress site's `/wp-content/plugins/` directory.
3. Activate the plugin through the WordPress admin dashboard under **Plugins**.

---

## Setup

1. Go to **Settings > AffiliateDeal Booster** in the WordPress dashboard.
2. Enter one or more affiliate network API endpoints in JSON format, one URL per line. Each endpoint should return deals as JSON arrays with these fields per deal: `title`, `link`, `discount` (optional), `expire_date` (optional, format YYYY-MM-DD).
3. Save changes.
4. Use the shortcode `[affiliate_deals]` in posts, pages, or widgets to display available deals.
5. The plugin will automatically refresh the deals hourly, or you can manually trigger a refresh from the settings page.

---

## Usage

- Add the shortcode `[affiliate_deals]` where you want the deal list to appear.
- Customize the affiliate API URLs as needed to include your preferred affiliate networks.
- Optionally style the deal list using CSS targeting `.adb-deal-list` for your theme customization.

---

## FAQ

**Q: What format should my affiliate API endpoints return?**  
A: The endpoints should return JSON arrays of deals. Each deal object must have `title` and `link` fields and may optionally include `discount` and `expire_date`.

**Q: Can I use this plugin without any affiliate APIs?**  
A: The plugin needs at least one affiliate API endpoint to fetch deals; otherwise, it will display no deals.

**Q: How often are deals updated?**  
A: Deals are updated automatically every hour by default via WordPress cron, or you can manually refresh them from the settings page.

---

## Support

For support, feature requests, or to report bugs, please contact the plugin author or open an issue on the plugin's GitHub repository (if available).

---

## License

This plugin is licensed under the GPLv2 or later.
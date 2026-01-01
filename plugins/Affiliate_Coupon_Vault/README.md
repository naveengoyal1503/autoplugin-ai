# Affiliate Coupon Vault

A lightweight, self-contained WordPress plugin for bloggers and affiliate marketers to create, manage, and display exclusive coupons with affiliate tracking.

## Features

- **Custom Post Type for Coupons**: Easily add coupons with title, description, image, affiliate link, coupon code, and discount percentage.
- **Shortcode Support**: Use `[acv_coupons limit="10" category="tech"]` to display coupons anywhere.
- **Widget Integration**: Drag-and-drop widget for sidebars with customizable limits.
- **Responsive Design**: Clean, mobile-friendly coupon cards with discount badges and CTA buttons.
- **Admin Settings**: Global disclaimer for affiliate compliance.
- **SEO-Friendly**: Public post type for indexing coupon pages.
- **Freemium Ready**: Pro version can add analytics, expiration dates, auto-testing, and API integrations.

## Installation

1. Download the plugin ZIP or copy the PHP code into a file named `affiliate-coupon-vault.php`.
2. Upload via **Plugins > Add New > Upload Plugin** or FTP to `/wp-content/plugins/affiliate-coupon-vault/`.
3. Activate the plugin.
4. Go to **Coupons > Add New** to create your first coupon.

## Setup

1. **Add Coupons**: In the admin, navigate to **Coupons > Add New**. Fill in details, add affiliate link, code, and discount in the meta box.
2. **Configure Settings**: Visit **Coupons > Settings** to set a default affiliate disclaimer.
3. **Display Options**:
   - Shortcode: `[acv_coupons]` for all recent coupons.
   - Widget: Add to any sidebar via **Appearance > Widgets**.
4. **Pro Tip**: Create category pages for SEO, e.g., `/coupons/tech/`.

## Usage

- **On Pages/Posts**: Insert shortcode for dynamic coupon grids.
- **Affiliate Tracking**: All links include `rel="nofollow"` and open in new tabs.
- **Customization**: Edit CSS via **Appearance > Customize > Additional CSS**.
- **Monetization**: Earn commissions on clicks; upgrade path for premium features like click tracking.

## Premium Features (Future)

- Click/conversion analytics
- Coupon expiration and auto-hide
- Import from affiliate networks
- White-label branding

Support: Contact via plugin page. Contributions welcome on GitHub.
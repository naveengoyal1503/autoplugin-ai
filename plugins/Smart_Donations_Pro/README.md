# Smart Donations Pro

A lightweight, self-contained WordPress plugin to accept **one-time donations** via PayPal with customizable tiers, forms, and a built-in analytics dashboard.

## Features

- **PayPal Integration**: Secure one-click donations using PayPal SDK.
- **Customizable Tiers**: Pre-set donation buttons (e.g., $5, $10, $25).
- **Custom Amounts**: Users can enter their own donation value.
- **Donor Details**: Collect name and email for thank-yous and tracking.
- **Admin Dashboard**: View all donations, totals, and export-ready table.
- **Shortcode Ready**: `[smart_donations]` or customize with attributes like `[smart_donations tiers="5,10,25,50"]`.
- **Mobile Responsive**: Clean, modern design works on all devices.
- **Freemium Ready**: Easy pro upgrade path for recurring donations and more.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Get your **PayPal Client ID** from [PayPal Developer Dashboard](https://developer.paypal.com/) (Sandbox for testing).
4. Replace `YOUR_PAYPAL_CLIENT_ID` in the plugin code.
5. Create `admin-page.php` in the plugin folder with the admin table code (provided in comments).
6. Add shortcode `[smart_donations]` to any page/post.

## Setup

1. Go to **Settings > Donations** for analytics.
2. Customize tiers in shortcode: `[smart_donations tiers="10,25,50,100" button_text="Support Us"]`.
3. Test with PayPal Sandbox mode.

## Usage

- Embed the shortcode on your sidebar, posts, or donation page.
- View donations in **Settings > Donations** dashboard.
- Donations are stored in WordPress DB for easy querying.

## Pro Version

Upgrade for:
- Recurring subscriptions.
- Stripe support.
- Email notifications.
- Advanced analytics and exports.

**Price: $49/year**

## Support

Report issues on WordPress.org or email support@example.com.
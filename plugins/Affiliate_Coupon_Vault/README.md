# Affiliate Coupon Vault

## Features

- **Easy Coupon Management**: Add unlimited coupons via admin with title, code, affiliate link, and description.[1][2]
- **Click Tracking**: Tracks clicks on coupons for performance analytics (pro: advanced reporting).[3]
- **Shortcode Integration**: Use `[acv_coupon id="0"]` to display coupons anywhere.[5]
- **Conversion Boost**: Exclusive coupons improve affiliate earnings and reader value.[1][2]
- **Freemium Model**: Free core features; pro unlocks unlimited coupons, analytics dashboard, API integrations ($49/year).
- **Mobile-Responsive**: Clean, professional design works on all devices.

## Installation

1. Upload the `affiliate-coupon-vault` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Coupon Vault** to add your coupons in JSON format.
4. Use shortcode `[acv_coupon id="0"]` in posts/pages (ID from your coupon list).

## Setup

1. In admin, enter coupons as JSON array:
   
   [
     {"title":"20% Off","code":"SAVE20","afflink":"https://yourafflink.com","description":"Exclusive deal"}
   ]
   
2. Save settings.
3. Embed shortcode and test clicks (tracks to affiliate link).

## Usage

- **Display Coupon**: `[acv_coupon id="0"]` shows reveal button; clicking tracks and redirects.
- **Analytics**: Check `acv_clicks_[ID]` options for basic counts (pro: dashboard).
- **Customization**: Style via CSS in shortcode output.
- **Monetization Tip**: Partner with brands for custom codes to boost uniqueness and commissions.[1][2]

## Pro Upgrade

- Unlimited coupons & categories
- Detailed analytics & export
- Auto-expiring coupons
- Email capture integration

Support: Contact via plugin page. Updates ensure compatibility.
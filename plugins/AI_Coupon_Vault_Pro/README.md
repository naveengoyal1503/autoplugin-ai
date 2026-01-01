# AI Coupon Vault Pro

## Features

- **AI-Powered Coupon Generation** (Premium): Auto-generate personalized coupons using OpenAI integration.
- **Easy Coupon Management**: Add/edit coupons via intuitive admin dashboard with JSON import/export.
- **Affiliate-Ready**: Trackable affiliate links with nofollow for SEO compliance.
- **Shortcode Display**: Use `[ai_coupon_vault]` or `[ai_coupon_vault limit="3"]` to showcase coupons anywhere.
- **Expiry Management**: Automatically hide expired coupons.
- **Responsive Design**: Mobile-friendly coupon grids.
- **Freemium Model**: Free core features; Pro unlocks AI, analytics, unlimited coupons ($49/year).

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress Admin > Plugins.
3. Go to **Coupon Vault** in admin menu to add coupons.

## Setup

1. Navigate to **Coupon Vault** dashboard.
2. Enter coupons in JSON format:
   
   {"coupons":[
     {"code":"SAVE20","desc":"20% Off","afflink":"https://aff.link","expiry":"2026-12-31"}
   ]}
   
3. Click **Save Coupons**.
4. Add `[ai_coupon_vault]` to any post/page.

## Usage

- **Frontend**: Coupons display as clickable reveals with affiliate links.
- **Customization**: Style via CSS classes like `.ai-coupon-vault`, `.coupon-item`.
- **Pro Features**: Enter OpenAI API key for auto-generation (e.g., "Generate 5 coupons for SaaS tools").
- **Monetization Tip**: High-conversion coupons boost affiliate earnings[1][2].

## Support
Contact support@example.com. Upgrade to Pro for priority support.
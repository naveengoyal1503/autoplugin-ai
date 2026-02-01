# Smart Donation Pro

## Features
- **Easy Donation Buttons**: Add customizable PayPal donation buttons via shortcode `[sdp_donate amount="10" label="Support Us" currency="USD" goal="1000"]`[1][3].
- **Progress Bars**: Track donation goals with `[sdp_progress id="campaign1" goal="1000"]` for visual fundraising[1].
- **One-Time & Recurring**: Supports tips, buy-me-coffee style donations, and goal tracking for memberships[1][6].
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Admin Dashboard**: Simple settings to configure PayPal email and view progress.
- **Freemium Ready**: Premium unlocks Stripe, analytics, unlimited goals ($29/year)[2][4].

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via WordPress Admin > Plugins.
3. Go to Settings > Donation Pro, enter your PayPal email.
4. Add shortcodes to posts/pages/widgets.

## Setup
1. **Configure PayPal**: In settings, add your verified PayPal business email[3].
2. **Create Button**: Use `[sdp_donate]` with custom amount/label/goal.
3. **Track Progress**: Pair with `[sdp_progress]`; donations auto-update bars via AJAX.
4. **Test**: Click button to simulate PayPal redirect.

## Usage
- **Blog Donations**: Place buy-coffee buttons in posts for reader tips[3][6].
- **Membership Sites**: Use progress for tiered goals (e.g., unlock content at 100%)[1].
- **Non-Profits**: Goal bars for campaigns, one-time payments[1].
- **Customization**: Style via CSS classes like `.sdp-donate-btn`.

## Premium Features
- Stripe integration for cards.
- Exportable analytics.
- Unlimited campaigns & custom branding.

**Support**: Contact support@example.com. Licensed under GPL v2.
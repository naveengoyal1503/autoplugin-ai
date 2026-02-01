# Smart Donation Pro

A lightweight, self-contained WordPress plugin to add professional donation buttons and goal trackers to your site. Perfect for bloggers, creators, and non-profits to monetize content via PayPal donations.[1][3]

## Features
- **Customizable donation buttons** with preset amounts (e.g., $5, $10, $25) and custom input.
- **Visual progress bars** for fundraising goals to boost engagement and conversions.
- **PayPal integration** for secure one-time payments (recurring in premium).
- **Shortcode-based**: Easy embed anywhere with `[smart_donation]`.
- **Tracks total donations** site-wide for real-time progress.
- **Mobile-responsive** design with inline CSS/JS for speed.
- **Freemium-ready**: Core free, upsell pro for analytics, Stripe, subscriptions.[4]

## Installation
1. Download and upload the plugin PHP file to `/wp-content/plugins/smart-donation-pro.php`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Donation Pro** to enter your PayPal email.
4. Use shortcode `[smart_donation amount="5,10,25" goal="1000" currency="$"]` in posts/pages.

## Setup
1. **Configure PayPal**: In admin settings, add your PayPal business email.
2. **Customize shortcode**:
   - `amount`: Comma-separated presets (default: "5,10,25").
   - `goal`: Target amount for progress bar (e.g., 1000).
   - `button_text`: Call-to-action (e.g., "Buy Me a Coffee").
   - `currency`: Symbol (e.g., "$", "€").
3. Test on a staging site; donations redirect to PayPal.

## Usage
- Embed in sidebar/widget: `[smart_donation]`.
- Goal tracker: `[smart_donation goal="5000"]`.
- Track totals in admin settings.

**Pro Upgrade**: Recurring donations, email notifications, analytics dashboard ($49/year).

## Screenshots
*(Imagine: Sleek button with green progress bar)*

## Changelog
**1.0.0**: Initial release with PayPal and goals.
# Smart Donation Pro

A lightweight, powerful donation plugin for WordPress sites. Collect one-time and optional recurring donations with customizable forms, live progress bars, and basic analytics. Perfect for creators, non-profits, and bloggers.

## Features
- **Customizable donation forms** via shortcode `[sdp_donation_form]` with preset amounts and email capture.
- **Live progress bars** with `[sdp_progress_bar id="goal1" goal="1000"]` to show donation progress.
- **Recurring donation option** (checkbox for monthly pledges).
- **Stripe-ready** (configure keys in settings; processes simulated donations).
- **Admin dashboard** for Stripe keys and donation logs (uses custom DB table).
- **Mobile-responsive** design with clean, modern styling.
- **Real-time updates** without page reloads using AJAX.

## Installation
1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > SDP Settings** to enter your Stripe keys (optional for full payment processing).

## Setup
1. **Add Donation Form**: Use shortcode `[sdp_donation_form goal="500" amounts="5,10,25,50,100" button_text="Support Us!"]`.
2. **Add Progress Bar**: Use `[sdp_progress_bar id="campaign1" goal="1000"]` (match `id` to form's `data-goal`).
3. **Stripe Integration**:
   - Sign up at [stripe.com](https://stripe.com) and get your publishable/secret keys.
   - Paste in **Settings > SDP Settings**.
4. **View Donations**: Check your site's database table `wp_sdp_donations` or extend with premium analytics.

## Usage
- Embed forms in posts, pages, sidebars, or widgets.
- Track totals via progress bars that auto-update on donations.
- Customize amounts, goals, and text via shortcode attributes.
- For production, extend `process_donation()` method to integrate full Stripe Checkout.

## Premium Upgrade
Unlock recurring Stripe subscriptions, email notifications, donor management, and export reports for $29/year.

## Support
Report issues on WordPress.org forums or email support@example.com.
# Smart Donation Pro

A lightweight, self-contained WordPress plugin to easily accept donations via PayPal with customizable buttons, fundraising goals, progress bars, and basic analytics.

## Features

- **Customizable Donation Buttons**: Use shortcodes to place donation forms anywhere with custom amounts and text.
- **Fundraising Goals**: Set targets and display real-time progress bars.
- **Donation Tracking**: Logs all donations in a database with donor details.
- **PayPal Integration**: One-click redirect to PayPal for secure payments.
- **Mobile-Responsive**: Works perfectly on all devices.
- **Freemium Upsell**: Free core with premium features teased in admin.

## Installation

1. Download the plugin ZIP.
2. In WordPress Admin: Plugins > Add New > Upload Plugin.
3. Activate the plugin.
4. Go to Settings > Donation Pro to configure PayPal email and goals.

## Setup

1. **Configure Settings**:
   - Navigate to **Settings > Donation Pro**.
   - Enter your PayPal email address.
   - Add goals (e.g., ID: 1, Target: 1000).
   - Save changes.

2. **Embed on Site**:
   - Use shortcode: `[smart_donation amount="10" button_text="Support Us!" goal="1"]`
   - Parameters:
     - `amount`: Default donation amount (default: 10)
     - `button_text`: Button label (default: "Donate Now")
     - `goal`: Goal ID for progress bar (default: 0, no bar)

## Usage

- Place shortcodes in posts, pages, or widgets.
- Visitors enter amount, click donate, and are redirected to PayPal.
- Track donations in the database (viewable via phpMyAdmin or extend for admin dashboard).
- Progress bars update automatically based on completed donations.

## Premium Features (Coming Soon)

- Recurring subscriptions.
- Advanced analytics dashboard.
- Custom themes and email notifications.

## Support

Report issues on WordPress.org forums or email support@example.com.

**Upgrade to Pro for unlimited goals and analytics!**
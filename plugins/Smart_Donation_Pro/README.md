# Smart Donation Pro

## Description

**Smart Donation Pro** is a simple yet powerful WordPress plugin to collect donations on your site. It features customizable donation buttons, visual progress bars toward your goal, seamless PayPal integration for one-time payments, and basic analytics. Perfect for bloggers, creators, and non-profits.

## Features

- **Customizable Shortcode**: Use `[sdp_donate]` with attributes like `amount`, `button_text`, `show_progress`.
- **Progress Bar**: Visual indicator of donations raised vs. goal.
- **PayPal Integration**: Secure one-time payments.
- **Admin Dashboard**: Set goal, PayPal email, and view current total.
- **Mobile Responsive**: Works on all devices.
- **Lightweight**: Single-file, no bloat.

Premium features (future): Recurring payments, advanced analytics, custom themes.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Upload and activate.
4. Go to **Settings > Donation Pro** to configure.

## Setup

1. In **Settings > Donation Pro**:
   - Enter your **Donation Goal** (e.g., 1000).
   - Add your **PayPal Email**.
   - Save settings.
2. Add the shortcode to any post/page: `[sdp_donate amount="10" button_text="Buy Me a Coffee" show_progress="true"]`.

## Usage

- **Basic Button**: `[sdp_donate]`
- **Custom Amount**: `[sdp_donate amount="50"]`
- **No Progress**: `[sdp_donate show_progress="false"]`

Donations update the progress in real-time (admin-tracked). View stats in settings.

## Support

Report issues on WordPress.org forums. Premium support available.

## Changelog

**1.0.0**
- Initial release.
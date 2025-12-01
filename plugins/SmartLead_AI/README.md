# SmartLead AI

SmartLead AI is an AI-powered lead generation WordPress plugin that dynamically creates personalized opt-in forms and popups to increase your email list and conversions. It adapts form headlines and call-to-actions based on visitor behavior and current page context, removing the need for manual customization.

## Features

- AI-simulated dynamic headlines and button texts based on page content or visitor context
- Lightweight, single-file plugin with embedded styles and JavaScript for quick deployment
- Popup opt-in form with email validation and AJAX submission
- Cookie-based display control to avoid annoying repeat views
- Simple integration point for email marketing services (can be extended)
- Free basic functionality with options to add premium AI and analytics in future releases

## Installation

1. Download the `smartlead-ai.php` file.
2. Upload it to your WordPress `/wp-content/plugins/` directory.
3. Activate the plugin through the WordPress admin dashboard.

## Setup

Currently, SmartLead AI works out of the box with intelligent defaults. No setup needed.

### Extending

Developers can extend the `handle_form_submission` method to connect with their email marketing service API.

## Usage

Once activated, the opt-in form popup will appear on your site for new visitors on pages based on the context:

- Homepage visitors see a general subscription call.
- Category pages show category-specific headlines.
- Single posts show headlines referencing the post title.

Visitors can enter their email and subscribe. Successful subscriptions trigger a thank-you message and set a cookie to avoid repeat popups.

## Support

For support or feature requests, please open an issue on the plugin repository or contact the author.

---

Develop your email list smartly with SmartLead AI for WordPress.
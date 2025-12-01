# AffiliateBoost Pro

AffiliateBoost Pro is a WordPress plugin designed to help bloggers, content creators, and affiliate marketers maximize their affiliate marketing revenue by managing affiliate links and dynamically recommending relevant links to visitors.

## Features

- Easy management of affiliate links via admin interface (JSON format)
- Shortcode to display affiliate links anywhere on your site
- Dynamic randomized link order for better engagement
- Responsive and clean front-end display
- Freemium model ready for expansion with advanced personalization and multi-network support

## Installation

1. Upload the plugin PHP file to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress 'Plugins' menu.
3. Navigate to the 'AffiliateBoost Pro' menu under the admin dashboard.
4. Add your affiliate links in the specified JSON format.

## Setup

- Input your affiliate links as a JSON array with objects containing `name` and `url` keys. Example:

  
  [
    {"name":"Product A", "url":"http://example.com/affiliate-link-a"},
    {"name":"Service B", "url":"http://example.com/affiliate-link-b"}
  ]
  

- Save the settings.

## Usage

- Use the shortcode `[affiliateboost_links]` in any post, page, or widget to show your affiliate links.
- Links will display in a randomized order each time for better click-through rates.

## Upgrade to Premium (Planned)

- AI-driven personalized affiliate recommendations
- Support for multiple affiliate networks and tracking
- Advanced analytics and reporting
- Priority support

---

## Support

For support, please open an issue in the plugin repository or contact the developer directly.

---

## License

GPLv2 or later
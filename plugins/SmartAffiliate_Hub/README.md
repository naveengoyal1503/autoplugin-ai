# SmartAffiliate Hub

## Description

SmartAffiliate Hub is a comprehensive affiliate marketing management plugin that empowers WordPress site owners to track, organize, and maximize their affiliate link revenue. With advanced analytics, link management, and performance tracking, this plugin transforms your WordPress site into a powerful affiliate marketing machine.

## Features

- **Affiliate Link Management**: Create, organize, and manage unlimited affiliate links with custom link codes
- **Click Tracking**: Automatically track every click on your affiliate links with detailed analytics
- **Performance Dashboard**: View real-time statistics including total links, total clicks, and monthly performance
- **Analytics Reports**: Detailed performance metrics for each affiliate program showing click counts and conversion data
- **Shortcode Integration**: Easily embed affiliate links in posts and pages using simple shortcodes
- **Commission Rate Tracking**: Track commission rates for each affiliate program
- **Flexible Settings**: Customize commission rates, enable/disable tracking, and configure email reporting
- **Database-Backed**: All data stored securely in WordPress database with proper indexing
- **Freemium Model**: Free tier with essential features; premium tier unlocks advanced analytics and API access

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Navigate to SmartAffiliate menu in the WordPress admin
4. Configure your settings under Settings tab

## Setup

### Initial Configuration

1. Go to **SmartAffiliate > Settings**
2. Set your default commission rate percentage
3. Enable click tracking (recommended)
4. Add your email for periodic reports
5. Save settings

### Adding Affiliate Links

1. Go to **SmartAffiliate > Affiliate Links**
2. Fill in the following details:
   - **Link Code**: Unique identifier (e.g., "amazon-book-review")
   - **Original URL**: The actual affiliate URL from the program
   - **Program**: Name of the affiliate program (e.g., "Amazon Associates")
   - **Commission %**: Your commission rate for this link
3. Click "Add Link"

## Usage

### Using Affiliate Links in Posts

Embed affiliate links in your WordPress posts and pages using the `[affiliate_link]` shortcode:


[affiliate_link code="amazon-book-review" text="Get this book on Amazon" class="custom-class"]


**Shortcode Parameters:**
- `code` (required): The link code you created
- `text`: Display text for the link (default: "Click here")
- `class`: CSS class for styling (default: "sah-affiliate-link")

### Monitoring Performance

1. Visit **SmartAffiliate > Dashboard** to see overview statistics
2. Check **SmartAffiliate > Analytics** for detailed performance by program
3. View total clicks, monthly trends, and conversion data

### Best Practices

- Use descriptive link codes that indicate the product or program
- Regularly review analytics to identify top-performing links
- Group related products under consistent program names
- Place affiliate links naturally within relevant content
- Clearly disclose affiliate relationships to your audience
- Test different commission structures to optimize earnings

## Monetization

SmartAffiliate Hub uses a freemium model:

- **Free Tier**: Core link management, basic analytics, dashboard
- **Premium Tier** ($9.99/month): Advanced analytics, link cloaking, weekly performance reports, API access, priority support

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.7+

## Support

For issues, feature requests, or questions, visit the plugin support page or documentation.

## License

GPL-2.0+
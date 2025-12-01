# DynamicAffiliateHub

A powerful affiliate link management and tracking plugin for WordPress that streamlines your affiliate marketing efforts with advanced analytics, automatic link optimization, and commission tracking.

## Features

- **Centralized Link Management**: Create, organize, and manage all your affiliate links from a single dashboard
- **Click & Conversion Tracking**: Automatically track clicks and conversions for each affiliate link with detailed analytics
- **Commission Tracking**: Monitor earnings per affiliate program and individual links in real-time
- **Easy Shortcodes**: Insert affiliate links anywhere with simple shortcodes like `[affiliate_link code="amazon" text="Check Price"]`
- **Advanced Analytics Dashboard**: View performance metrics including click-through rates, conversion rates, and earnings
- **SEO Friendly**: Automatic rel="nofollow" tag addition and link code customization
- **Redirect Delay Options**: Configure redirect delays to track user behavior
- **Link Performance Sorting**: Identify your best-performing affiliate links
- **Program Organization**: Group and organize links by affiliate programs
- **Security**: Nonce verification and data sanitization throughout

## Installation

1. Download the DynamicAffiliateHub plugin files
2. Upload the plugin folder to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin panel under Plugins
4. Navigate to Affiliate Hub in your WordPress dashboard to get started

## Setup

### Initial Configuration

1. Go to **Affiliate Hub > Settings** in your WordPress dashboard
2. Configure the following options:
   - **Enable Click Tracking**: Toggle on to track all affiliate link clicks
   - **Redirect Delay (ms)**: Set a delay before redirecting to the affiliate URL (useful for analytics)
   - **Add rel="nofollow"**: Automatically add rel="nofollow" to affiliate links for SEO

### Adding Your First Affiliate Link

1. Navigate to **Affiliate Hub > Manage Links**
2. Fill in the form:
   - **Link Code**: A unique identifier (e.g., "amazon-widget", "shareasale-app")
   - **Affiliate URL**: Your full affiliate link from the program
   - **Display Text**: Optional text for reference
   - **Program Name**: Name of the affiliate program (e.g., "Amazon Associates")
   - **Commission Rate**: Your commission percentage for this link
3. Click "Add Link"

## Usage

### Inserting Affiliate Links in Posts/Pages

Use the shortcode format to insert your affiliate links:


[affiliate_link code="link-code" text="Link Display Text" class="optional-css-class"]


**Example**:

[affiliate_link code="amazon" text="View on Amazon" class="highlight-button"]


### Shortcode Parameters

- `code` (required): The link code you created in the Manage Links section
- `text` (optional): Display text for the link (default: "Click Here")
- `class` (optional): CSS class to add to the link element

### Viewing Analytics

1. Go to **Affiliate Hub > Analytics** to see:
   - Total clicks per link
   - Click-through rates
   - Conversion numbers
   - Conversion rates
   - Earnings per program
   - Top-performing links

### Managing Your Links

- **Edit Links**: Click on any link code in the Manage Links section to view details
- **Delete Links**: Use the Delete button to remove links you no longer use
- **Track Performance**: Use the Analytics page to monitor which links generate the most revenue

## Best Practices

- Use descriptive link codes that are easy to remember (e.g., "amazon-deals" instead of "aff1")
- Enable the nofollow option to comply with search engine affiliate link guidelines
- Monitor your analytics regularly to identify top performers
- Update commission rates when programs change their commission structure
- Test redirect delays to optimize for conversions
- Organize links by program to easily track earnings by source

## Monetization

This plugin is available in two versions:

- **Free Version**: Essential affiliate link management with basic tracking
- **Premium Version**: Advanced analytics, unlimited link tracking, API access, and priority support

## Support

For issues, feature requests, or questions, visit our support website or documentation portal.

## License

This plugin is licensed under GPL2. See the LICENSE file for details.

## Changelog

### Version 1.0.0
- Initial release
- Core affiliate link management
- Click and conversion tracking
- Analytics dashboard
- Shortcode support
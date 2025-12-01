# ContentRevenue Pro

## Description

ContentRevenue Pro is a comprehensive WordPress monetization plugin designed for bloggers and content creators who want to maximize income through multiple revenue streams. The plugin integrates affiliate link tracking, sponsored content management, and detailed analytics in one unified dashboard.

## Features

- **Affiliate Link Management**: Track clicks, conversions, and commissions for each affiliate link
- **Sponsored Content Tracking**: Record sponsored posts and payments from brand partnerships
- **Real-time Analytics Dashboard**: Monitor clicks, conversions, earnings, and sponsored revenue
- **Easy Shortcodes**: Simple shortcodes to embed affiliate links and sponsored content badges
- **Performance Tracking**: Track conversion rates and earnings per program
- **FTC Disclosure Support**: Automatically include FTC-compliant disclosures for sponsored content
- **Multi-Program Support**: Track affiliate links from different programs (Amazon, ClickBank, etc.)
- **Click Tracking**: Automatically logs clicks on affiliate links for performance analysis

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the ContentRevenue menu in the WordPress admin dashboard
4. Configure your affiliate programs and sponsored content settings

## Getting Started

### Setting Up Affiliate Links

1. Go to **ContentRevenue > Affiliate Links**
2. Enter the post ID where the affiliate link appears
3. Paste your affiliate URL
4. Give the link a descriptive name (e.g., "Best SEO Plugin")
5. Select the program (Amazon Associates, ShareASale, etc.)
6. Click **Add Affiliate Link**

### Recording Sponsored Content

1. Go to **ContentRevenue > Sponsored Content**
2. Enter the post ID of the sponsored article
3. Enter the brand name
4. Specify the payment amount and date
5. Confirm FTC disclosure inclusion
6. Click **Record Sponsored Content**

## Usage

### Embedding Affiliate Links

Use the shortcode in your posts:


[crp_affiliate_link id="1" text="Check Best Price on Amazon"]


**Parameters:**
- `id`: The affiliate link ID from your database
- `text`: The clickable link text (default: "Click here")

### Adding Sponsored Content Badges

Add this shortcode to your sponsored posts:


[crp_sponsored_content brand="Acme Corp" content="This post is brought to you by Acme Corp, offering premium solutions for content creators."]


**Parameters:**
- `brand`: Name of the sponsoring brand
- `content`: Disclosure or promotional text

## Dashboard Metrics

The main dashboard displays:

- **Total Affiliate Clicks**: Cumulative clicks across all affiliate links
- **Commission Earned**: Total commission from affiliate conversions
- **Total Conversions**: Number of successful referrals
- **Sponsored Revenue**: Total earned from sponsored content partnerships

## Database Tables

The plugin creates two custom database tables:

### `wp_crp_affiliate_links`
- Stores affiliate link data
- Tracks clicks, conversions, and commission
- Links affiliate URLs to specific posts

### `wp_crp_sponsored_content`
- Records sponsored content partnerships
- Tracks brand payments and dates
- Maintains FTC disclosure status

## Monetization Strategy

ContentRevenue Pro employs a **freemium model**:

- **Free Version**: Core affiliate tracking, basic sponsored content management, essential analytics
- **Premium Version**: Advanced analytics with conversion funnels, automated reporting, multi-channel tracking, affiliate program templates, and commission forecasting

## FAQ

**Q: How are affiliate clicks tracked?**
A: The plugin injects JavaScript that logs click events when users click affiliate links. This data is stored in the database for later analysis.

**Q: Can I use this with multiple affiliate programs?**
A: Yes! The plugin supports unlimited affiliate programs. Simply tag each link with its program name when creating it.

**Q: Is the FTC disclosure automatic?**
A: The sponsored content shortcode includes a disclosure badge by default. You can customize the disclosure text as needed.

**Q: Will this plugin slow down my site?**
A: No. Click tracking is handled asynchronously via AJAX, and the plugin loads minimal assets on the frontend.

## Support & Documentation

For additional help, visit the plugin settings page or consult the documentation included with your installation.

## License

GPL v2 or later
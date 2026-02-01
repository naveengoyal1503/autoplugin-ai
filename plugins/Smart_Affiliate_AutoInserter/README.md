# Smart Affiliate AutoInserter

**Automatically monetize your WordPress content by inserting relevant Amazon affiliate links based on keywords.**

## Features

- **Smart Keyword Matching**: Automatically detects keywords in posts/pages and inserts matching affiliate links.
- **Customizable Limits**: Set max links per post to avoid over-optimization.
- **Easy Setup**: Configure your Amazon Affiliate tag and keyword-ASIN pairs in seconds.
- **Mobile-Responsive**: Links render perfectly on all devices.
- **Freemium Model**: Free version for basics; **Pro** adds API integration, click tracking, A/B testing, and analytics dashboard.
- **SEO-Friendly**: Uses `nofollow` and `noopener` for compliance.

**Pro Features (Upgrade for $49/year)**:
- Real Amazon Product API lookups for dynamic titles/images.
- Performance analytics and revenue tracking.
- Advanced targeting (page-specific, post-type filters).
- Unlimited keyword pairs and A/B link testing.

## Installation

1. Download and upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate via **Plugins > Installed Plugins**.
3. Go to **Settings > Affiliate Inserter** to configure.

## Setup

1. **Get Amazon Affiliate Tag**: Sign up at [Amazon Associates](https://affiliate-program.amazon.com/) and grab your tag (e.g., `yourid-20`).
2. **Add Keywords & ASINs**: In settings, enter pairs like `earbuds -> B08N5WRWNW`.
3. **Set Max Links**: Default 3 per post.
4. **Save & Test**: Publish a post with keywords to see links auto-insert.

**Example**:
- Keyword: `smartwatch`
- ASIN: `B07RF1XD36`
- Generates: `<a href="https://amazon.com/dp/B07RF1XD36?tag=yourid-20">Smartwatch</a>`

## Usage

- Write content naturally with target keywords.
- Links insert automatically in `the_content`.
- **Pro Tip**: Use tiered pricing psychology like $9.99 links for higher conversions.
- Monitor earnings in Amazon Associates dashboard.

## FAQ

**Does it slow my site?** No, lightweight with no external calls in free version.
**Works with Gutenberg/Classic?** Yes, filters `the_content` universally.
**Support?** Free users: WordPress.org forums. Pro: Priority email support.

## Changelog

**1.0.0** - Initial release.

**Upgrade to Pro today for 40% higher conversions with dynamic content!**
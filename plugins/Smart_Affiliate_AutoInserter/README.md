# Smart Affiliate AutoInserter

**Automatically monetize your WordPress site by inserting relevant Amazon affiliate links into posts and pages.**

## Features

- **Smart Auto-Insertion**: Detects keywords in content and replaces them with your Amazon affiliate links.
- **Easy Setup**: Add your affiliate tag and keyword:ASIN pairs via simple admin settings.
- **Customizable**: Control max links per post (1-10) to avoid over-optimization.
- **SEO-Friendly**: Links use `nofollow sponsored` attributes.
- **Freemium Model**: Free for basics; **Pro** adds AI context matching, analytics dashboard, unlimited keywords, and priority support ($49/year).

## Target Users
Bloggers, niche site owners, and affiliate marketers looking for hands-off passive income.

## Installation

1. Download the plugin ZIP.
2. In WordPress Admin: **Plugins > Add New > Upload Plugin**.
3. Activate the plugin.
4. Go to **Settings > Affiliate Inserter** to configure.

## Setup

1. **Get Amazon Affiliate Tag**: Sign up at [Amazon Associates](https://affiliate-program.amazon.com/) and note your tag (e.g., `yourtag-20`).
2. **Find ASINs**: Search Amazon products; copy the ASIN (e.g., `B08N5WRWNW` from URL).
3. **Configure**:
   - Enter your **Amazon Affiliate Tag**.
   - Add keywords and ASINs (one per line: `keyword:ASIN` e.g., `laptop:B08N5WRWNW`).
   - Set **Max Links per Post** (default: 3).
4. Save settings. Links auto-insert on published posts/pages.

**Example Keywords List:**

laptop:B08N5WRWNW
headphones:B07ZPC9QD4
coffee maker:B08L5LG4D8


## Usage

- Write content with keywords (e.g., "best laptop").
- Publish: Links auto-appear as `<a href="amazon-url?tag=yourtag-20">laptop</a>`.
- **Test**: View a post; check source for links. Track commissions in Amazon Associates dashboard.
- **Pro Features**: Upgrade for click analytics, A/B testing, and auto-ASIN suggestions.

## FAQ

**Does it slow my site?** No, lightweight regex processing only on single posts.

**Compliant with Amazon TOS?** Yes, uses proper nofollow/sponsored; limit density.

**Pro Upgrade?** Visit [example.com/pro](https://example.com/pro) for advanced features.

## Support
Submit tickets via WordPress.org forums (free) or Pro support portal.

**Start earning passive income today!** 🚀
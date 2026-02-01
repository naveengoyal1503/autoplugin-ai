# Smart Affiliate Link Manager

## Description
**Smart Affiliate Link Manager** automatically detects affiliate links in your posts, cloaks them for better tracking, logs clicks with IP and analytics, and provides a dashboard for stats. Free version includes basic cloaking and 7-day click stats. Premium adds A/B testing, conversion tracking, auto-optimization, and more.

## Features
- **Automatic Link Detection & Cloaking**: Finds Amazon, ClickBank, and custom affiliate links in content and replaces with trackable cloaked versions.
- **Click Tracking**: Logs clicks with IP, user agent, and timestamps in a secure database.
- **Admin Dashboard**: View recent stats and manage settings.
- **Freemium Ready**: Premium upsell with API key gating for advanced features like A/B testing.
- **SEO-Friendly**: Maintains nofollow and target blank attributes.
- **Lightweight**: Single-file plugin, no dependencies.

**Premium Features** (via subscription):
- A/B link testing
- Conversion rate analytics
- Auto-optimization suggestions
- Exportable reports
- Priority support

## Installation
1. Upload the `smart-affiliate-link-manager.php` file to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit **Settings > Affiliate Manager** to configure.

## Setup
1. Go to **Settings > Affiliate Manager**.
2. (Optional) Enter Premium API Key from [example.com/premium](https://example.com/premium).
3. Add affiliate links to your posts—they auto-cloak on frontend.
4. View click stats in the dashboard.

## Usage
- Write posts with raw affiliate URLs (e.g., `https://amazon.com/product123`).
- Plugin converts to `/go/?salml=...` cloaked links.
- Clicks redirect to original URL after logging.
- Stats show daily clicks (free) or advanced metrics (premium).

## Screenshots
*(Add screenshots in production: dashboard, cloaked link example)*

## Changelog
**1.0.0**
- Initial release with cloaking, tracking, and freemium setup.

## Upgrade to Premium
Subscribe at [example.com/premium](https://example.com/premium) for $9/month and enter your API key to unlock pro features.
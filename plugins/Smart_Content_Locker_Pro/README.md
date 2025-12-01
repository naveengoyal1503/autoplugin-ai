# Smart Content Locker Pro

A powerful WordPress plugin that gates premium content behind user actions to build email lists, increase engagement, and boost monetization.

## Features

- **Multiple Unlock Actions**: Email signup, social media sharing, or referral-based unlocking
- **Email Provider Integration**: Built-in support for Mailchimp, ConvertKit, and custom webhooks
- **Simple Shortcode Interface**: Easy-to-use shortcode system for any WordPress page or post
- **Cookie-Based Tracking**: Persistent content unlocking with customizable expiration
- **AJAX-Powered**: Seamless user experience without page reloads
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **Conversion Analytics**: Track which content performs best for list-building
- **Freemium Model**: Basic features free with premium add-ons for advanced analytics

## Installation

1. Download the plugin files to `/wp-content/plugins/smart-content-locker/`
2. Activate the plugin through the WordPress Admin Dashboard
3. Go to **Content Locker** in the admin menu to configure settings
4. Connect your email provider (Mailchimp, ConvertKit, or custom webhook)

## Setup

### Step 1: Email Provider Configuration

1. Navigate to **Content Locker** → **Settings**
2. Select your email provider from the dropdown
3. Enter your API key (Mailchimp or ConvertKit)
4. Save settings

### Step 2: Get Your Mailchimp API Key

- Log into your Mailchimp account
- Click your profile icon → **Account**
- Select **Extras** → **API Keys**
- Generate a new API key and copy it

## Usage

### Basic Email Lock

Wrap any content with the shortcode to gate it behind an email signup:


[content_locker action="email" title="Unlock Premium Content"]
Your premium content goes here
[/content_locker]


### Social Media Share Lock

Require users to share on social media to unlock:


[content_locker action="social_share" title="Share to Unlock"]
Exclusive content for sharers
[/content_locker]


### Referral-Based Lock

Require users to refer friends:


[content_locker action="referral" title="Refer 3 Friends"]
Premium content unlocked by referrals
[/content_locker]


## Shortcode Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `action` | string | email | Unlock action: email, social_share, referral |
| `title` | string | Unlock Premium Content | Title shown on lock overlay |
| `id` | string | auto-generated | Unique identifier for the locker |

## Monetization Strategies

**Build Email Lists**: Convert readers into subscribers for email marketing campaigns

**Affiliate Marketing**: Promote products within locked content to qualified audiences

**Sponsored Content**: Gate sponsored posts behind email signups for better sponsor ROI

**Content Upsell**: Lock premium content behind email to generate leads for premium courses or services

## Frequently Asked Questions

**Q: Can I use multiple lockers on one page?**
A: Yes! Each locker functions independently with unique tracking.

**Q: How long do unlock cookies last?**
A: By default, 30 days. Filter with `scl_cookie_expiry` to customize.

**Q: Is this GDPR compliant?**
A: The plugin collects emails through explicit opt-in forms. Ensure your privacy policy discloses email collection practices.

**Q: Can I customize the unlock button styling?**
A: Yes, modify `assets/frontend.css` or use CSS filters in your theme.

## Support & Documentation

For issues, feature requests, or documentation: https://smartcontentlocker.com/docs

## Upgrade to Pro

Pro features include:
- A/B testing for unlock actions
- Advanced conversion analytics dashboard
- Unlimited content blocks
- Priority email support
- Custom integration assistance

## License

This plugin is licensed under the GPL v2 or later. See LICENSE file for details.
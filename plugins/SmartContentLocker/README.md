# SmartContentLocker

## Description

SmartContentLocker is a powerful WordPress plugin that enables content creators, bloggers, and course instructors to lock premium content behind user actions like email signups, social shares, or payments. This creates a seamless lead generation and monetization tool that increases engagement while building your subscriber list.

## Features

**Free Version:**
- Lock content behind email signup forms
- Lock content behind social share requirements
- Easy-to-use shortcode integration
- Built-in subscriber database
- Simple unlock overlays with customizable messages
- Basic analytics dashboard

**Premium Version (Recommended for Advanced Users):**
- Multiple email service integrations (Mailchimp, ConvertKit, ActiveCampaign)
- Payment gateway integration (Stripe, PayPal)
- A/B testing for unlock messages and actions
- Advanced analytics with conversion tracking
- Custom unlock templates
- Conditional unlock rules based on user roles
- Drip campaigns and automation

## Installation

1. Download the SmartContentLocker plugin
2. Go to **WordPress Admin > Plugins > Add New**
3. Click **Upload Plugin** and select the plugin file
4. Click **Install Now** and then **Activate**
5. Navigate to **SmartContentLocker** in the admin menu to configure settings

## Setup

### Basic Configuration

1. Go to **SmartContentLocker** > **Settings**
2. Enter your email service API key (for Mailchimp integration in free version)
3. Configure unlock message defaults
4. Save your settings

### Email Service Setup

- **Mailchimp**: Obtain your API key from Mailchimp account settings and paste it into the plugin settings
- For Premium: Connect to ConvertKit, ActiveCampaign, or other supported services

## Usage

### Basic Shortcode

Use the `[content_locker]` shortcode to protect any content:


[content_locker type="email" message="Subscribe to unlock this exclusive content!"]
  <p>This is your premium content that will be hidden until the user subscribes.</p>
[/content_locker]


### Social Share Lock


[content_locker type="share" message="Share this article to unlock the full content!"]
  <p>Premium content here.</p>
[/content_locker]


### Payment Lock (Premium)


[content_locker type="payment" price="9.99" currency="USD" message="Purchase access for $9.99"]
  <p>Exclusive paid content.</p>
[/content_locker]


### Shortcode Parameters

| Parameter | Description | Default |
|-----------|-------------|----------|
| `type` | Unlock method: email, share, payment | email |
| `message` | Custom unlock message | "Unlock this content" |
| `id` | Unique locker ID (auto-generated) | auto |
| `price` | Price for payment locks (Premium) | N/A |
| `currency` | Currency code (Premium) | USD |

## Monetization Models

SmartContentLocker supports multiple revenue streams:

1. **Email Capture**: Build your mailing list for future sales
2. **Social Amplification**: Encourage content sharing for organic reach
3. **Direct Sales**: Lock premium courses, guides, or resources behind payments
4. **Freemium Model**: Offer basic content free, premium content for subscribers
5. **Affiliate Integration**: Create locked content for affiliate products

## Examples

### Blog Monetization
- Lock in-depth guides behind email signup
- Generate leads while providing value

### Course Creator
- Lock course modules behind payment
- Preview content free, full access paid

### Newsletter Builder
- Gate valuable content to grow subscriber base
- Use for email list segmentation

## Frequently Asked Questions

**Q: Will this affect my SEO?**
A: The locked content is still in the HTML (just visually hidden), so search engines can crawl it. Premium version includes SEO optimization options.

**Q: Can I customize the unlock button appearance?**
A: In the free version, basic CSS customization is available. Premium version includes full design customization.

**Q: Does this support email list exports?**
A: Yes, export subscriber lists from the admin dashboard in CSV format.

**Q: What payment gateways are supported?**
A: Free version requires Premium for payment integration. Premium supports Stripe and PayPal.

## Support

For issues, feature requests, or support: contact support@smartcontentlocker.com

## License

This plugin is licensed under GPL v2 or later.
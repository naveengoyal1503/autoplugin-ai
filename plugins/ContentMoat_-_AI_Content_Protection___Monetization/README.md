# ContentMoat - AI Content Protection & Monetization

## Features

- **AI Content Protection**: Implements DRM-like protection to prevent scraping by AI crawlers
- **Paywall System**: Create preview-based paywalls with customizable word counts
- **Per-Post Configuration**: Set protection type and pricing individually for each post
- **Usage Analytics**: Track who accesses protected content and when
- **Freemium Model**: Basic features free, premium tier ($9.99/month) for advanced analytics
- **Easy Integration**: Works seamlessly with existing WordPress posts
- **Subscription Ready**: Foundation for recurring revenue implementation

## Installation

1. Upload the ContentMoat plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to ContentMoat in the admin menu

## Setup

1. Go to **ContentMoat > Settings**
2. Choose your default protection type (Preview or Full)
3. Set your default unlock price
4. Configure individual posts as needed

## Usage

### Protect Individual Posts

1. Edit any post
2. In the post settings, select:
   - **Protection Type**: Preview or Full
   - **Preview Words**: Number of words to show before paywall (default: 100)
   - **Price**: Cost to unlock content
3. Update/Publish the post

### Use Paywall Shortcode


[contentmoat_paywall post_id="123"]


### View Analytics

Visit **ContentMoat > Dashboard** to see:
- Total access attempts
- Number of protected posts
- Recent activity log with IP addresses and timestamps

## Monetization Model

**Free Tier**: 
- Basic content protection
- Post-level settings
- Basic analytics

**Premium Tier ($9.99/month)**:
- Advanced DRM technology
- Email notifications for unlocks
- Detailed revenue reports
- API access for content licensing
- Priority support

## Future Enhancements

- Integration with Stripe/PayPal for direct payments
- Email capture on paywall unlock
- A/B testing of paywall messaging
- Bot detection and CAPTCHA integration
- Content watermarking with user identification
- Export analytics to CSV

## Support

For questions or issues, visit our documentation or contact support@contentmoat.com

## License

GPL v2 or later
# Smart Affiliate Popups

**AI-powered popup plugin** that automatically generates and displays personalized **affiliate link popups** to **boost conversions** and monetize your WordPress site.

## Features

- **Smart Popups**: Automatically shows engaging popups with affiliate links after 10 seconds (30% show rate to avoid annoyance).
- **Customizable Links**: Easily add your Amazon, hosting, or any affiliate links via admin settings.
- **Mobile-Responsive**: Clean, modern design works on all devices.
- **Conversion Optimized**: Personalized deals with images and CTAs.
- **Freemium Model**: Free core features; **Pro** adds A/B testing, geo-targeting, unlimited popups, and AI-generated links.
- **Lightweight**: Single-file, no bloat, self-contained.

## Installation

1. Download the plugin ZIP.
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Upload and activate **Smart Affiliate Popups**.
4. Go to **Settings > Affiliate Popups** to configure.

## Setup

1. **Enable Popups**: Check the box in settings.
2. **Add Affiliate Links**: Paste JSON array in the textarea, e.g.:
   
   [
     {"text": "Get 50% off hosting!", "url": "https://yourafflink.com"},
     {"text": "Amazon deals", "url": "https://amazon.com/?tag=yourid"}
   ]
   
3. Save settings. Popups appear automatically on frontend.
4. **Pro Tip**: Use your real affiliate IDs for instant monetization.

## Usage

- Popups trigger on page load (10s delay, random 30% users).
- Users see overlay popup with your link; click "Claim Now" to convert.
- Track conversions via your affiliate dashboard.
- **Customize**: Edit CSS/JS in plugin code if needed (advanced).

## Pro Version

Upgrade for $49/year:
- AI-powered link generation based on site content.
- A/B testing for higher conversions (up to 28% boost per pricing psych[1]).
- Advanced targeting (geo, device, behavior).
- Analytics dashboard.

## Support

Report issues on WordPress.org forums. Pro support included with upgrade.

## Changelog

**1.0.0**: Initial release with core popup functionality.
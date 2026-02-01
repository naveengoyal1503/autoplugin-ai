# AI Viral Giveaway Booster

## Features

- **AI-Powered Optimization**: Analyzes entries to suggest high-converting actions (Pro).
- **Viral Entry Mechanics**: Email signup, social shares, referrals with multipliers.
- **Customizable Campaigns**: Set prizes, durations, templates via shortcode.
- **Real-Time Leaderboard**: Boosts competition and engagement.
- **Mobile-Responsive Design**: Works on all devices.
- **Easy Integration**: One shortcode `[ai_giveaway id="1"]`.
- **Freemium Model**: Unlimited free campaigns with 100 entry cap; Pro removes limits.

**Pro Features ($49/year)**: A/B testing, OpenAI integration, Zapier/Mailchimp hooks, premium templates, analytics dashboard.

## Installation

1. Upload the plugin ZIP to WordPress admin > Plugins > Add New > Upload.
2. Activate the plugin.
3. Go to Settings > AI Viral Giveaway to enter OpenAI API key (optional for Pro).
4. Create campaign data manually or via admin (future updates add UI).

## Setup

1. In wp-config.php or admin, set campaigns:
   php
   update_option('ai_vgb_campaigns', ['1' => ['prize' => 'iPhone 16', 'end' => '2026-03-01']]);
   
2. Add shortcode to any page/post: `[ai_giveaway id="1"]`.
3. Customize CSS/JS in plugin file for branding.

## Usage

- **Embed Anywhere**: Pages, posts, sidebars.
- **Track Entries**: Data stored in `ai_vgb_entries_1` option.
- **Monetize Traffic**: 30-50% lift in emails per campaign[1].
- **Pro Upgrade**: Visit example.com/pro for advanced AI features.

## Support
Contact support@example.com. Free updates guaranteed.
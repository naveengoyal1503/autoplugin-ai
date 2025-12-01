# ContentVault Pro - WordPress Membership Plugin

## Overview

ContentVault Pro is a powerful membership and content gating plugin that enables WordPress site owners to monetize their content through subscription-based membership tiers. Build a loyal community, manage recurring revenue, and protect premium content with ease.

## Features

- **Flexible Membership Tiers**: Create unlimited subscription tiers with custom pricing and billing periods
- **Recurring Payments**: Automated recurring billing with monthly and yearly options
- **Content Gating**: Restrict access to posts, pages, and custom content to paying members
- **Member Management Dashboard**: View active members, subscription status, and billing history
- **Payment Gateway Integration**: Support for Stripe and PayPal
- **Email Integration**: Connect with Mailchimp, ConvertKit, and Constant Contact for subscriber management
- **Shortcodes**: Simple shortcodes for membership forms and gated content
- **Analytics**: Track member growth, retention rates, and revenue metrics
- **Tiered Access Levels**: Offer different content access levels for different subscription tiers
- **Freemium Model**: Free tier with 1 membership level, premium tier with unlimited features

## Installation

1. Upload the `contentvault-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin dashboard
3. Navigate to **ContentVault Pro** in the left menu
4. Configure payment settings and create your first membership tier

## Setup

### Step 1: Configure Payment Methods

1. Go to **ContentVault Pro > Settings**
2. Enter your Stripe API Key or PayPal email address
3. Set the redirect URL for after successful purchases
4. Save settings

### Step 2: Create Membership Tiers

1. Navigate to **ContentVault Pro > Membership Tiers**
2. Click **Add New Tier**
3. Enter tier name, description, price, and billing period
4. Define included features
5. Save tier

### Step 3: Gate Your Content

- Use `[cvp_gated_content tier_id="1"]Your premium content here[/cvp_gated_content]` to restrict content to specific tiers
- Or assign posts to the **Gated Content** post type for automatic gating

## Usage

### Display Membership Form

Add this shortcode to any page to display your membership tiers:


[cvp_membership_form]


### Gate Specific Content

Wrap content with the gated content shortcode:


[cvp_gated_content tier_id="2"]
This content is only visible to members of tier 2
[/cvp_gated_content]


### Manage Members

View all members and their subscription status in **ContentVault Pro > Members**.

## Monetization

ContentVault Pro uses a freemium monetization model:

- **Free Tier**: 1 membership tier, unlimited members, basic analytics
- **Premium Tier ($49/year)**: Unlimited tiers, advanced analytics, email integrations, priority support, API access

Upgrade your license in the plugin settings to unlock premium features.

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+
- Active payment processor account (Stripe or PayPal)

## Support

Visit our documentation at contentvaultpro.com or email support@contentvaultpro.com for assistance.
# Affiliate Smart Link Manager

## Description
Affiliate Smart Link Manager automatically detects configured keywords in your WordPress content and converts them into cloaked affiliate links. This lets you maximize affiliate revenue by automating link management and cloaking.

## Features
- Automatically link keywords in posts/pages to your affiliate URLs
- Cloak affiliate links behind your own domain for trust and better tracking
- Manage a list of keywords and corresponding affiliate URLs via an easy admin UI
- Redirect users from simple short links (e.g., yoursite.com/?aslm=keyword) to affiliate URLs
- No external dependencies, fully self-contained plugin

## Installation
1. Upload the plugin PHP file to your `/wp-content/plugins/` directory or install via WordPress plugin uploader if you package it.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the "Affiliate Links" menu in your admin dashboard
4. Add your affiliate keywords and their destination URLs
5. Save changes

## Setup
- Add as many keyword → affiliate URL pairs as you want
- The plugin will automatically replace the first occurrence of each keyword in your post/page content with a cloaked affiliate link

## Usage
- Insert keywords naturally in your posts to have affiliate links inserted automatically
- Use short URLs such as `?aslm=keyword` anywhere to redirect directly to the affiliate URL

## Notes
- Only the first occurrence of a keyword per post is linked to avoid overlinking
- Links open in a new tab and have rel="nofollow noopener" attributes for SEO best practices

Enjoy maximizing your affiliate income with minimal effort!
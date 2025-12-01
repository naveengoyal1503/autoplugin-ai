<?php
/*
Plugin Name: Affiliate Deal Tracker
Description: Automatically aggregate and display affiliate coupons and deals on your WordPress site.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Tracker.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealTracker {

  public function __construct() {
    add_shortcode('affiliate_deals', [$this, 'render_deals']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
  }

  public function enqueue_styles() {
    wp_register_style('affiliate_deal_tracker_css', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_style('affiliate_deal_tracker_css');
  }

  private function fetch_deals() {
    // Simulated deals data - In real use, connect to affiliate APIs or RSS feeds
    $deals = [
      [
        'title' => '25% Off on Premium Hosting',
        'url' => 'https://affiliate.example.com/deal1?ref=yourid',
        'description' => 'Save 25% on top-rated hosting plans.',
        'expiry' => '2026-01-31'
      ],
      [
        'title' => 'Buy One Get One Free Ebook',
        'url' => 'https://affiliate.example.com/deal2?ref=yourid',
        'description' => 'Exclusive BOGO offer on bestselling ebooks.',
        'expiry' => '2026-03-15'
      ],
      [
        'title' => '10% Off WordPress Themes',
        'url' => 'https://affiliate.example.com/deal3?ref=yourid',
        'description' => 'Get discount on popular WP themes.',
        'expiry' => '2025-12-31'
      ],
    ];

    $today = date('Y-m-d');
    // Filter out expired deals
    $valid_deals = array_filter($deals, function($deal) use ($today) {
      return $deal['expiry'] >= $today;
    });

    return $valid_deals;
  }

  public function render_deals($atts) {
    $deals = $this->fetch_deals();
    if (empty($deals)) {
      return '<p>No affiliate deals available at the moment. Check back soon!</p>';
    }
    $output = '<div class="affiliate-deal-tracker">';
    foreach ($deals as $deal) {
      $output .= '<div class="adt-deal">';
      $output .= '<h3><a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($deal['title']) . '</a></h3>';
      $output .= '<p>' . esc_html($deal['description']) . '</p>';
      $output .= '<small>Expires on: ' . esc_html($deal['expiry']) . '</small>';
      $output .= '</div>';
    }
    $output .= '</div>';
    return $output;
  }
}

new AffiliateDealTracker();

/**
 * Minimal styles for deals output
 */
add_action('wp_head', function() {
  echo '<style>
  .affiliate-deal-tracker {border:1px solid #ddd; padding:15px; background:#f9f9f9; margin-bottom:20px;}
  .adt-deal {margin-bottom:15px;}
  .adt-deal h3 {margin:0 0 5px 0; font-size:1.25em;}
  .adt-deal p {margin:0 0 5px 0; color:#555;}
  .adt-deal small {color:#999;}
  </style>';
});

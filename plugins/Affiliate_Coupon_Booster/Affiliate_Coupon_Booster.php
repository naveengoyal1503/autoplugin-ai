<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Aggregates and displays affiliate coupons dynamically to boost affiliate sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
  private $coupons = [];

  public function __construct() {
    add_shortcode('affiliate_coupons', [$this, 'render_coupons']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
  }

  public function enqueue_styles() {
    wp_register_style('acb-style', plugins_url('style.css', __FILE__));
    wp_enqueue_style('acb-style');
  }

  private function fetch_coupons() {
    // Simulated static coupon data for demo purposes
    $this->coupons = [
      [
        'title' => '10% off at Example Store',
        'code' => 'EXAMPLE10',
        'url' => 'https://affiliate.example.com/track?coupon=EXAMPLE10',
        'description' => 'Save 10% on all products at Example Store using this exclusive coupon.',
        'expires' => '2026-01-31'
      ],
      [
        'title' => 'Free Shipping at ShopX',
        'code' => 'FREESHIP',
        'url' => 'https://affiliate.shopx.com/referral?coupon=FREESHIP',
        'description' => 'Get free shipping on orders over $50 at ShopX.',
        'expires' => '2026-03-15'
      ]
    ];
  }

  public function render_coupons($atts) {
    $this->fetch_coupons();

    ob_start();
    echo '<div class="acb-coupon-list">';
    foreach ($this->coupons as $coupon) {
      $expired = strtotime($coupon['expires']) < time();
      echo '<div class="acb-coupon"'.($expired ? ' style="opacity:0.6;"' : '').'>
        <h3>' . esc_html($coupon['title']) . '</h3>
        <p>' . esc_html($coupon['description']) . '</p>
        <p><strong>Coupon Code:</strong> <span class="acb-code">' . esc_html($coupon['code']) . '</span></p>
        <a href="' . esc_url($coupon['url']) . '" target="_blank" rel="noopener noreferrer" class="acb-btn"' . ($expired ? ' onclick="return false;" style="pointer-events:none;"' : '') . '>Redeem Offer</a>
      </div>';
    }
    echo '</div>';

    return ob_get_clean();
  }
}

new AffiliateCouponBooster();
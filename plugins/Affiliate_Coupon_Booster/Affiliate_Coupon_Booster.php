/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Booster
 * Description: Dynamic affiliate coupon finder and display plugin to increase conversions.
 * Version: 1.0
 * Author: Your Name
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
  private $coupons;

  public function __construct() {
    add_shortcode('affiliate_coupons', array($this, 'render_coupons'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  public function enqueue_scripts() {
    wp_enqueue_style('acb-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('acb-script', plugins_url('script.js', __FILE__), array('jquery'), false, true);
  }

  private function fetch_coupons() {
    // Simulated coupon data; production would call affiliate APIs or scrape trusted sources
    return array(
      array('code' => 'SAVE10', 'desc' => '10% off on electronics', 'url' => 'https://affiliate.example.com/deal1', 'expiry' => '2026-12-31'),
      array('code' => 'FREESHIP', 'desc' => 'Free shipping on orders over $50', 'url' => 'https://affiliate.example.com/deal2', 'expiry' => '2025-12-31'),
      array('code' => 'BOGO50', 'desc' => 'Buy One Get One 50% off', 'url' => 'https://affiliate.example.com/deal3', 'expiry' => '2026-06-30')
    );
  }

  public function render_coupons($atts) {
    $this->coupons = $this->fetch_coupons();
    ob_start();
    ?>
    <div class="affiliate-coupons">
      <h3>Exclusive Coupons</h3>
      <ul>
        <?php foreach ($this->coupons as $coupon):
          $expired = strtotime($coupon['expiry']) < time();
          if ($expired) continue; // Skip expired coupons
          ?>
          <li>
            <strong><?php echo esc_html($coupon['code']); ?></strong> - <?php echo esc_html($coupon['desc']); ?>
            <a href="<?php echo esc_url($coupon['url']); ?>" target="_blank" rel="nofollow noopener noreferrer">Use Coupon</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php
    return ob_get_clean();
  }
}

new AffiliateCouponBooster();

// Inline style and script for simplicity
add_action('wp_head', function() {
  echo '<style>
    .affiliate-coupons {background:#f9f9f9; border:1px solid #ddd; padding:15px; max-width:400px;}
    .affiliate-coupons h3 {margin-top: 0; font-family: Arial,sans-serif;}
    .affiliate-coupons ul {list-style:none; padding:0;}
    .affiliate-coupons li {margin-bottom:10px; font-family: Arial,sans-serif;}
    .affiliate-coupons a {margin-left:10px; text-decoration:none; color:#0073aa;}
    .affiliate-coupons a:hover {text-decoration:underline;}
  </style>';
});

<?php
/*
Plugin Name: Affiliate Deal Booster
Plugin URI: https://example.com/affiliate-deal-booster
Description: Auto-aggregates and displays affiliate coupons and deals to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPLv2 or later
Text Domain: affiliate-deal-booster
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealBooster {
    private $deals_cache_key = 'adb_cached_deals';
    private $deals_cache_time = 3600; // 1 hour cache

    public function __construct() {
        add_shortcode('affiliate_deals', array($this, 'render_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_ajax_adb_fetch_deals', array($this, 'ajax_fetch_deals'));
        add_action('wp_ajax_nopriv_adb_fetch_deals', array($this, 'ajax_fetch_deals'));
    }

    public function enqueue_styles() {
        wp_register_style('adb_styles', plugins_url('affiliate-deal-booster.css', __FILE__));
        wp_enqueue_style('adb_styles');
    }

    private function fetch_external_deals() {
        // Simulated external affiliate deals fetch
        // In reality, would integrate APIs like Rakuten, Impact, or Amazon.
        $deals = array(
            array('title' => '50% off on Tech Gadgets', 'url' => 'https://affiliate.example.com/tech', 'code' => 'TECH50', 'desc' => 'Grab 50% discount on all tech gadgets.', 'expiry' => '2026-01-31'),
            array('title' => '20% Cashback on Fashion', 'url' => 'https://affiliate.example.com/fashion', 'code' => 'FASHION20', 'desc' => 'Get 20% cashback on fashion brands.', 'expiry' => '2025-12-31'),
            array('title' => 'Free Shipping Over $50', 'url' => 'https://affiliate.example.com/shipping', 'code' => 'FREESHIP', 'desc' => 'Free shipping on orders over $50.', 'expiry' => '2026-03-15'),
        );
        return $deals;
    }

    private function get_deals() {
        $cached = get_transient($this->deals_cache_key);
        if ($cached !== false) {
            return $cached;
        }
        $deals = $this->fetch_external_deals();
        set_transient($this->deals_cache_key, $deals, $this->deals_cache_time);
        return $deals;
    }

    public function render_deals_shortcode($atts) {
        $deals = $this->get_deals();
        ob_start();
        ?>
        <div class="adb-deals-container">
            <?php foreach ($deals as $deal): ?>
                <div class="adb-deal-item">
                    <h3 class="adb-deal-title"><?php echo esc_html($deal['title']); ?></h3>
                    <p class="adb-deal-desc"><?php echo esc_html($deal['desc']); ?></p>
                    <p class="adb-deal-code"><strong>Code:</strong> <span><?php echo esc_html($deal['code']); ?></span></p>
                    <p class="adb-deal-expiry"><small>Expires: <?php echo esc_html($deal['expiry']); ?></small></p>
                    <a class="adb-deal-link" href="<?php echo esc_url($deal['url']); ?>" target="_blank" rel="nofollow noopener noreferrer">Redeem Offer</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_fetch_deals() {
        // Endpoint for fetching deals via AJAX (future enhancement for dynamic filtering)
        wp_send_json_success($this->get_deals());
    }
}

new AffiliateDealBooster();

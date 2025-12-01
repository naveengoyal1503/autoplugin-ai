<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Automatically aggregates and displays affiliate coupons from multiple networks to boost your affiliate earnings.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
Text Domain: affiliate-coupon-booster
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
    private $coupons_option_key = 'acb_cached_coupons';
    private $coupons_expiry_option_key = 'acb_coupons_expiry';
    private $cache_duration = 3600; // 1 hour cache

    public function __construct() {
        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        // Schedule coupon fetching
        add_action('acb_fetch_coupons_hook', array($this, 'fetch_and_cache_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        if (!wp_next_scheduled('acb_fetch_coupons_hook')) {
            wp_schedule_event(time(), 'hourly', 'acb_fetch_coupons_hook');
        }
        $this->fetch_and_cache_coupons();
    }

    public function deactivate() {
        wp_clear_scheduled_hook('acb_fetch_coupons_hook');
        delete_option($this->coupons_option_key);
        delete_option($this->coupons_expiry_option_key);
    }

    public function enqueue_styles() {
        wp_register_style('acb_styles', plugins_url('acb_styles.css', __FILE__));
        wp_enqueue_style('acb_styles');
    }

    private function get_mock_coupons_data() {
        // In a real premium version, fetch from affiliate networks API here
        $mock = array(
            array(
                'title' => '50% Off Summer Sale',
                'code' => 'SUMMER50',
                'url' => 'https://affiliatesite.com/deal/summer50?ref=youraffiliateid',
                'description' => 'Get 50% off on all summer collection items.',
                'expiry' => date('Y-m-d', strtotime('+10 days'))
            ),
            array(
                'title' => 'Free Shipping Over $50',
                'code' => 'FREESHIP50',
                'url' => 'https://affiliatesite.com/deal/freeship50?ref=youraffiliateid',
                'description' => 'Enjoy free shipping on orders over $50.',
                'expiry' => date('Y-m-d', strtotime('+5 days'))
            ),
            array(
                'title' => '10% Off Electronics',
                'code' => 'ELECTRO10',
                'url' => 'https://affiliatesite.com/deal/electro10?ref=youraffiliateid',
                'description' => 'Save 10% on all electronics purchases.',
                'expiry' => date('Y-m-d', strtotime('+15 days'))
            ),
        );
        return $mock;
    }

    public function fetch_and_cache_coupons() {
        // Placeholder for real API data fetching
        $coupons = $this->get_mock_coupons_data();
        update_option($this->coupons_option_key, $coupons);
        update_option($this->coupons_expiry_option_key, time() + $this->cache_duration);
    }

    public function render_coupons_shortcode($atts) {
        $coupons = get_option($this->coupons_option_key, array());
        if (empty($coupons)) {
            $this->fetch_and_cache_coupons();
            $coupons = get_option($this->coupons_option_key, array());
        }

        $html = '<div class="acb-coupons-container">';
        foreach ($coupons as $coupon) {
            $html .= '<div class="acb-coupon">';
            $html .= '<h3 class="acb-title">' . esc_html($coupon['title']) . '</h3>';
            $html .= '<p class="acb-description">' . esc_html($coupon['description']) . '</p>';
            $html .= '<p><strong>Code: </strong><span class="acb-code" tabindex="0" role="button" aria-label="Copy coupon code ' . esc_attr($coupon['code']) . '">' . esc_html($coupon['code']) . '</span></p>';
            $html .= '<a href="' . esc_url($coupon['url']) . '" target="_blank" rel="nofollow noopener noreferrer" class="acb-button">Use Coupon</a>';
            $html .= '<p class="acb-expiry">Expires: ' . esc_html($coupon['expiry']) . '</p>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // Include copy-to-clipboard script inline
        $html .= '<script>document.addEventListener("DOMContentLoaded",function(){const els=document.querySelectorAll(".acb-code");els.forEach(el=>{el.addEventListener("click",()=>{navigator.clipboard.writeText(el.textContent).then(()=>{alert("Coupon code copied: " + el.textContent);});});});});</script>';

        return $html;
    }
}

new AffiliateCouponBooster();

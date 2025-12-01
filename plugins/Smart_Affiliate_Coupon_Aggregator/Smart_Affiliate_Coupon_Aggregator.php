<?php
/*
Plugin Name: Smart Affiliate Coupon Aggregator
Description: Aggregates affiliate coupons from multiple retailers and displays them with affiliate links in a customizable widget.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Aggregator.php
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponAggregator {
    private $coupons_option_key = 'saca_coupons_data';
    private $last_update_option_key = 'saca_last_update';
    private $update_interval = 86400; // 24 hours

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_shortcode('saca_coupons', array($this, 'coupons_shortcode'));
        add_action('init', array($this, 'schedule_coupon_update'));
        add_action('saca_update_coupons_event', array($this, 'fetch_and_store_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('saca-main-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function activate() {
        if (!wp_next_scheduled('saca_update_coupons_event')) {
            wp_schedule_event(time(), 'daily', 'saca_update_coupons_event');
        }
        $this->fetch_and_store_coupons();
    }

    public function deactivate() {
        wp_clear_scheduled_hook('saca_update_coupons_event');
    }

    public function schedule_coupon_update() {
        if (!wp_next_scheduled('saca_update_coupons_event')) {
            wp_schedule_event(time(), 'daily', 'saca_update_coupons_event');
        }
    }

    public function fetch_and_store_coupons() {
        // In a real-world plugin, here APIs or scrapers would be used.
        // For demonstration, create static sample coupons with affiliate links.
        $sample_coupons = array(
            array(
                'title' => '20% Off Electronics at ShopZone',
                'code' => 'ELECTRO20',
                'description' => 'Save 20% on all electronics at ShopZone. Limited time offer!',
                'affiliate_url' => 'https://affiliate.shopzone.com/?ref=youraffiliateid',
                'expiry' => date('Y-m-d', strtotime('+30 days'))
            ),
            array(
                'title' => 'Free Shipping on Orders Over $50 at StyleCenter',
                'code' => 'FREESHIP50',
                'description' => 'Enjoy free shipping when you spend $50 or more.',
                'affiliate_url' => 'https://stylecenter.com/affiliate?aid=youraffiliateid',
                'expiry' => date('Y-m-d', strtotime('+15 days'))
            ),
            array(
                'title' => '15% Discount on Home Essentials at HomeLiving',
                'code' => 'HOME15',
                'description' => 'Get 15% off home essentials with this exclusive coupon.',
                'affiliate_url' => 'https://homeliving.com/aff?id=youraffiliateid',
                'expiry' => date('Y-m-d', strtotime('+20 days'))
            ),
        );

        update_option($this->coupons_option_key, $sample_coupons);
        update_option($this->last_update_option_key, time());
    }

    public function coupons_shortcode($atts) {
        $coupons = get_option($this->coupons_option_key, array());
        $now = current_time('Y-m-d');

        if (empty($coupons)) {
            return '<p>No coupons available at the moment. Please check back later.</p>';
        }

        $output = '<div class="saca-coupons">';
        foreach ($coupons as $coupon) {
            // Skip expired coupons
            if ($coupon['expiry'] < $now) {
                continue;
            }
            $output .= '<div class="saca-coupon">';
            $output .= '<h3 class="saca-coupon-title">' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p class="saca-coupon-desc">' . esc_html($coupon['description']) . '</p>';
            $output .= '<p class="saca-coupon-code">Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            $output .= '<p><a class="saca-coupon-link" href="' . esc_url($coupon['affiliate_url']) . '" target="_blank" rel="nofollow noopener">Shop Now & Save</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }
}

new SmartAffiliateCouponAggregator();

// Minimal CSS to style the coupons (embedded inline style)
add_action('wp_head', function(){
    echo '<style>.saca-coupons{max-width:600px;margin:20px auto;padding:10px;border:1px solid #ddd;background:#f9f9f9;border-radius:6px;}.saca-coupon{border-bottom:1px solid #eee;padding:10px 0;}.saca-coupon:last-child{border-bottom:none;}.saca-coupon-title{font-size:1.2em;color:#0073aa;margin:0 0 5px 0;}.saca-coupon-desc{margin:0 0 5px 0;color:#555;}.saca-coupon-code{font-weight:bold;margin:0 0 5px 0;color:#333;}.saca-coupon-link{display:inline-block;padding:6px 12px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;}.saca-coupon-link:hover{background:#005177;}</style>';
});

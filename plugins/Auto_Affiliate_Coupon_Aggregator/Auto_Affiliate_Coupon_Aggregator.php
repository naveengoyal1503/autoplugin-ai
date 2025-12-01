/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Auto_Affiliate_Coupon_Aggregator.php
*/
<?php
/**
 * Plugin Name: Auto Affiliate Coupon Aggregator
 * Plugin URI: https://example.com/auto-affiliate-coupon-aggregator
 * Description: Automatically fetch and display affiliate coupons from multiple sources.
 * Version: 1.0
 * Author: Your Name
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AutoAffiliateCouponAggregator {
    private $option_name = 'aaca_coupons_cache';
    private $cache_time = 3600; // 1 hour cache

    public function __construct() {
        add_shortcode('auto_affiliate_coupons', [$this, 'shortcode_render_coupons']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('aaca-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    // Sample static coupon providers for demo purposes
    private function fetch_coupons_from_source_1() {
        // In real plugin, fetch from API endpoint
        return [
            [
                'title' => '10% Off on Electronics',
                'code'  => 'ELEC10',
                'link'  => 'https://affiliate.example.com/deal1',
                'expiry' => date('Y-m-d', strtotime('+7 days'))
            ],
            [
                'title' => 'Free Shipping on Orders Over $50',
                'code'  => '',
                'link'  => 'https://affiliate.example.com/deal2',
                'expiry' => date('Y-m-d', strtotime('+10 days'))
            ]
        ];
    }

    private function fetch_coupons_from_source_2() {
        return [
            [
                'title' => '15% Off Sitewide',
                'code'  => 'SAVE15',
                'link'  => 'https://affiliate2.example.com/deal',
                'expiry' => date('Y-m-d', strtotime('+5 days'))
            ]
        ];
    }

    // Merge and cache coupons
    private function get_coupons() {
        $cached = get_option($this->option_name);
        if ($cached && isset($cached['time']) && (time() - $cached['time']) < $this->cache_time) {
            return $cached['data'];
        }

        $coupons = array_merge($this->fetch_coupons_from_source_1(), $this->fetch_coupons_from_source_2());

        // Filter expired coupons
        $today = date('Y-m-d');
        $coupons = array_filter($coupons, function($coupon) use ($today) {
            return $coupon['expiry'] >= $today;
        });

        // Update cache
        update_option($this->option_name, ['time' => time(), 'data' => $coupons]);

        return $coupons;
    }

    public function shortcode_render_coupons($atts) {
        $coupons = $this->get_coupons();
        if (empty($coupons)) {
            return '<p>No coupons available at the moment. Please check back later.</p>';
        }

        $output = '<div class="aaca-coupon-list">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="aaca-coupon-item">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            if (!empty($coupon['code'])) {
                $output .= '<p>Coupon Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            }
            $output .= '<p>Expires: ' . esc_html($coupon['expiry']) . '</p>';
            $output .= '<a class="aaca-apply-btn" href="' . esc_url($coupon['link']) . '" target="_blank" rel="nofollow noopener">Use Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }
}

new AutoAffiliateCouponAggregator();

?>
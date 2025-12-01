<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize revenue with smart affiliate link rotation, coupon display, and sponsored content management.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

define('WP_REVENUE_BOOSTER_VERSION', '1.0');

class WPRevenueBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));        
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('revenue_booster', array($this, 'shortcode_handler'));
    }

    public function init() {
        // Register custom post type for coupons and sponsored content
        register_post_type('wp_revenue_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
        register_post_type('wp_revenue_sponsored', array(
            'labels' => array('name' => 'Sponsored Content', 'singular_name' => 'Sponsored Post'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
    }

    public function admin_menu() {
        add_menu_page('Revenue Booster', 'Revenue Booster', 'manage_options', 'wp-revenue-booster', array($this, 'admin_page'));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>WP Revenue Booster</h1><p>Manage coupons, affiliate links, and sponsored content here.</p></div>';
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'coupon',
            'count' => 1
        ), $atts);

        $output = '';
        if ($atts['type'] === 'coupon') {
            $coupons = get_posts(array('post_type' => 'wp_revenue_coupon', 'numberposts' => $atts['count']));
            foreach ($coupons as $coupon) {
                $output .= '<div class="revenue-coupon"><strong>' . esc_html($coupon->post_title) . '</strong>: ' . esc_html($coupon->post_content) . '</div>';
            }
        } elseif ($atts['type'] === 'sponsored') {
            $posts = get_posts(array('post_type' => 'wp_revenue_sponsored', 'numberposts' => $atts['count']));
            foreach ($posts as $post) {
                $output .= '<div class="revenue-sponsored"><h3>' . esc_html($post->post_title) . '</h3><p>' . esc_html($post->post_content) . '</p></div>';
            }
        }
        return $output;
    }
}

new WPRevenueBooster();
?>
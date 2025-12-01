<?php
/*
Plugin Name: WP Revenue Booster
Description: Automate coupon/deal aggregation, affiliate link management, and sponsored content scheduling.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('init', array($this, 'register_post_types'));
        add_shortcode('revenue_booster_coupons', array($this, 'coupons_shortcode'));
        add_shortcode('revenue_booster_affiliates', array($this, 'affiliates_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function register_post_types() {
        register_post_type('revenue_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
        register_post_type('revenue_affiliate', array(
            'labels' => array('name' => 'Affiliate Links', 'singular_name' => 'Affiliate Link'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
        register_post_type('revenue_sponsored', array(
            'labels' => array('name' => 'Sponsored Content', 'singular_name' => 'Sponsored Post'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>WP Revenue Booster</h1>';
        echo '<p>Manage coupons, affiliate links, and sponsored content from here.</p>';
        echo '<p><a href="post-new.php?post_type=revenue_coupon">Add Coupon</a> | ';
        echo '<a href="post-new.php?post_type=revenue_affiliate">Add Affiliate Link</a> | ';
        echo '<a href="post-new.php?post_type=revenue_sponsored">Add Sponsored Post</a></p>';
        echo '</div>';
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_posts(array('post_type' => 'revenue_coupon', 'numberposts' => $atts['limit']));
        $output = '<ul class="revenue-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<li><strong>' . esc_html($coupon->post_title) . '</strong>: ' . esc_html($coupon->post_content) . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    public function affiliates_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $affiliates = get_posts(array('post_type' => 'revenue_affiliate', 'numberposts' => $atts['limit']));
        $output = '<ul class="revenue-affiliates">';
        foreach ($affiliates as $affiliate) {
            $output .= '<li><a href="' . esc_url($affiliate->post_content) . '" target="_blank">' . esc_html($affiliate->post_title) . '</a></li>';
        }
        $output .= '</ul>';
        return $output;
    }
}

new WP_Revenue_Booster();

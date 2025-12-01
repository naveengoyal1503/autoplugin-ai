/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Boost your WordPress site's revenue with automated coupons, affiliate tracking, and premium content management.
 * Version: 1.0
 * Author: Revenue Labs
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0');
define('WP_REVENUE_BOOSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WP_Revenue_Booster {

    public function __construct() {
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('revenue_coupon', array($this, 'coupon_shortcode'));
        add_shortcode('premium_content', array($this, 'premium_content_shortcode'));
    }

    public function init_plugin() {
        // Register custom post type for coupons
        register_post_type('revenue_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));

        // Register custom post type for premium content
        register_post_type('premium_content', array(
            'labels' => array('name' => 'Premium Content', 'singular_name' => 'Premium Content'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
    }

    public function add_admin_menu() {
        add_menu_page('Revenue Booster', 'Revenue Booster', 'manage_options', 'wp-revenue-booster', array($this, 'admin_page'), 'dashicons-chart-line');
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>WP Revenue Booster</h1><p>Manage coupons, affiliate links, and premium content access.</p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'revenue_coupon');
        $coupon = get_post($atts['id']);
        if (!$coupon) return '';
        return '<div class="revenue-coupon"><h3>' . esc_html($coupon->post_title) . '</h3><p>' . esc_html($coupon->post_content) . '</p></div>';
    }

    public function premium_content_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'premium_content');
        if (!is_user_logged_in()) return '<p>Please log in to access premium content.</p>';
        $content = get_post($atts['id']);
        if (!$content) return '';
        return '<div class="premium-content"><h3>' . esc_html($content->post_title) . '</h3><div>' . apply_filters('the_content', $content->post_content) . '</div></div>';
    }
}

new WP_Revenue_Booster();

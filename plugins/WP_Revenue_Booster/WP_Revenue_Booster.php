/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site's revenue by rotating high-converting affiliate offers, coupons, and sponsored content based on user behavior.
 * Version: 1.0.0
 * Author: Revenue Labs
 * Author URI: https://example.com
 * License: GPL2
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0.0');

class WP_Revenue_Booster {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('revenue_booster', array($this, 'shortcode'));
    }

    public function init() {
        // Register custom post type for offers
        $args = array(
            'public' => true,
            'label' => 'Revenue Offers',
            'supports' => array('title', 'editor', 'custom-fields'),
            'show_in_rest' => true
        );
        register_post_type('revenue_offer', $args);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/js/booster.js', array('jquery'), WP_REVENUE_BOOSTER_VERSION, true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        echo '<div class="wrap"><h1>WP Revenue Booster</h1>';
        echo '<p>Manage your affiliate offers, coupons, and sponsored content here.</p>';
        echo '<p><a href="' . admin_url('post-new.php?post_type=revenue_offer') . '" class="button button-primary">Add New Offer</a></p>';
        echo '</div>';
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all',
            'limit' => 3
        ), $atts, 'revenue_booster');

        $args = array(
            'post_type' => 'revenue_offer',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(
                array(
                    'key' => 'offer_type',
                    'value' => $atts['type'],
                    'compare' => 'IN'
                )
            )
        );

        $offers = new WP_Query($args);
        $output = '<div class="wp-revenue-booster-offers">';
        while ($offers->have_posts()) {
            $offers->the_post();
            $output .= '<div class="offer">
                <h3>' . get_the_title() . '</h3>
                <p>' . get_the_content() . '</p>
                <a href="' . get_post_meta(get_the_ID(), 'offer_link', true) . '" target="_blank" class="button">' . get_post_meta(get_the_ID(), 'offer_cta', true) . '</a>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }
}

new WP_Revenue_Booster();

// Create assets/js/booster.js file with basic JS for tracking and rotation
// Example: jQuery(document).ready(function($) {
//     // Track user engagement and rotate offers
// });
?>
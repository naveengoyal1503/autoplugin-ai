<?php
/*
Plugin Name: AffiliDeal Affiliate Coupon & Deal Manager
Plugin URI: https://example.com/affilideal
Description: Create and display affiliate coupons and deals to monetize your WordPress site.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliDeal__Affiliate_Coupon___Deal_Manager.php
License: GPL2
Text Domain: affilideal
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AffilIdeal {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_metaboxes'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_shortcode('affilideal_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => __('Coupons', 'affilideal'),
            'singular_name' => __('Coupon', 'affilideal'),
            'menu_name' => __('AffiliDeal Coupons', 'affilideal'),
            'add_new' => __('Add New Coupon', 'affilideal'),
            'add_new_item' => __('Add New Coupon', 'affilideal'),
            'edit_item' => __('Edit Coupon', 'affilideal'),
            'new_item' => __('New Coupon', 'affilideal'),
            'view_item' => __('View Coupon', 'affilideal'),
            'search_items' => __('Search Coupons', 'affilideal'),
            'not_found' => __('No coupons found', 'affilideal'),
            'not_found_in_trash' => __('No coupons found in Trash', 'affilideal'),
            'all_items' => __('All Coupons', 'affilideal')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-tickets-alt',
            'supports' => array('title', 'editor'),
            'show_in_rest' => true,
        );

        register_post_type('affilideal_coupon', $args);
    }

    public function add_coupon_metaboxes() {
        add_meta_box('affilideal_coupon_details', __('Coupon Details', 'affilideal'), array($this, 'coupon_details_metabox'), 'affilideal_coupon', 'normal', 'high');
    }

    public function coupon_details_metabox($post) {
        wp_nonce_field('affilideal_save_coupon', 'affilideal_coupon_nonce');

        $affiliate_url = get_post_meta($post->ID, '_affilideal_affiliate_url', true);
        $discount_code = get_post_meta($post->ID, '_affilideal_discount_code', true);
        $expire_date = get_post_meta($post->ID, '_affilideal_expire_date', true);

        echo '<p><label for="affilideal_affiliate_url">'.__('Affiliate URL', 'affilideal').':</label><br />';
        echo '<input type="url" id="affilideal_affiliate_url" name="affilideal_affiliate_url" value="' . esc_attr($affiliate_url) . '" size="50" required /></p>';

        echo '<p><label for="affilideal_discount_code">'.__('Discount Code (optional)', 'affilideal').':</label><br />';
        echo '<input type="text" id="affilideal_discount_code" name="affilideal_discount_code" value="' . esc_attr($discount_code) . '" size="30" /></p>';

        echo '<p><label for="affilideal_expire_date">'.__('Expiration Date (optional)', 'affilideal').':</label><br />';
        echo '<input type="date" id="affilideal_expire_date" name="affilideal_expire_date" value="' . esc_attr($expire_date) . '" /></p>';
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['affilideal_coupon_nonce']) || !wp_verify_nonce($_POST['affilideal_coupon_nonce'], 'affilideal_save_coupon')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ('affilideal_coupon' != $_POST['post_type']) {
            return $post_id;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        if (isset($_POST['affilideal_affiliate_url'])) {
            update_post_meta($post_id, '_affilideal_affiliate_url', esc_url_raw($_POST['affilideal_affiliate_url']));
        }

        if (isset($_POST['affilideal_discount_code'])) {
            update_post_meta($post_id, '_affilideal_discount_code', sanitize_text_field($_POST['affilideal_discount_code']));
        }

        if (isset($_POST['affilideal_expire_date'])) {
            update_post_meta($post_id, '_affilideal_expire_date', sanitize_text_field($_POST['affilideal_expire_date']));
        }
    }

    public function render_coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'show_expired' => 'no'
        ), $atts);

        $today = date('Y-m-d');

        $meta_query = array();
        if ($atts['show_expired'] === 'no') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_affilideal_expire_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_affilideal_expire_date',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        $args = array(
            'post_type' => 'affilideal_coupon',
            'posts_per_page' => intval($atts['count']),
            'meta_query' => $meta_query,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $coupons = new WP_Query($args);
        if (!$coupons->have_posts()) {
            return '<p>' . __('No coupons available.', 'affilideal') . '</p>';
        }

        ob_start();
        echo '<div class="affilideal-coupons">';

        while ($coupons->have_posts()) {
            $coupons->the_post();
            $affiliate_url = get_post_meta(get_the_ID(), '_affilideal_affiliate_url', true);
            $discount_code = get_post_meta(get_the_ID(), '_affilideal_discount_code', true);
            $expire_date = get_post_meta(get_the_ID(), '_affilideal_expire_date', true);

            echo '<div class="affilideal-coupon" style="border:1px solid #ccc;margin:10px;padding:10px;border-radius:5px;">';
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '<div>' . wpautop(get_the_content()) . '</div>';
            if ($discount_code) {
                echo '<p><strong>' . __('Discount Code:', 'affilideal') . '</strong> <code>' . esc_html($discount_code) . '</code></p>';
            }
            if ($expire_date) {
                echo '<p><small>' . sprintf(__('Expires on %s', 'affilideal'), esc_html($expire_date)) . '</small></p>';
            }
            echo '<p><a href="' . esc_url($affiliate_url) . '" target="_blank" rel="nofollow noopener noreferrer" class="affilideal-btn" style="background:#0073aa;color:#fff;padding:8px 15px;text-decoration:none;border-radius:3px;">'. __('Get Deal', 'affilideal') .'</a></p>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();

        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affilideal-style', plugin_dir_url(__FILE__) . 'affilideal-style.css', array(), '1.0');
    }
}

new AffilIdeal();

// Minimal CSS in same file for self-containment
add_action('wp_head', function() {
    echo '<style>.affilideal-coupons { max-width: 600px; margin: 0 auto; } .affilideal-btn:hover { background: #005177 !important; }</style>';
});

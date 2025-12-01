<?php
/*
Plugin Name: Affiliate Deal Booster
Plugin URI: https://example.com/plugins/affiliate-deal-booster
Description: Display and manage affiliate coupons and deals with automatic expiration and click tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPLv2 or later
Text Domain: affiliate-deal-booster
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateDealBooster {
    private $coupon_post_type = 'adb_coupon';

    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post', array($this, 'save_coupon_meta')); 
        add_shortcode('adb_coupons', array($this, 'shortcode_display_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_action('admin_init', array($this, 'schedule_cleanup'));
        add_action('adb_cleanup_hook', array($this, 'cleanup_expired_coupons'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => __('Affiliate Coupons', 'affiliate-deal-booster'),
            'singular_name' => __('Coupon', 'affiliate-deal-booster'),
            'add_new' => __('Add New Coupon', 'affiliate-deal-booster'),
            'add_new_item' => __('Add New Coupon', 'affiliate-deal-booster'),
            'edit_item' => __('Edit Coupon', 'affiliate-deal-booster'),
            'new_item' => __('New Coupon', 'affiliate-deal-booster'),
            'view_item' => __('View Coupon', 'affiliate-deal-booster'),
            'search_items' => __('Search Coupons', 'affiliate-deal-booster'),
            'not_found' => __('No coupons found', 'affiliate-deal-booster'),
            'not_found_in_trash' => __('No coupons found in Trash', 'affiliate-deal-booster'),
            'all_items' => __('All Coupons', 'affiliate-deal-booster'),
            'menu_name' => __('Affiliate Deals', 'affiliate-deal-booster'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-tag',
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
            'has_archive' => false,
            'rewrite' => false
        );
        register_post_type($this->coupon_post_type, $args);
    }

    public function add_coupon_meta_boxes() {
        add_meta_box('adb_coupon_details', __('Coupon Details', 'affiliate-deal-booster'), array($this, 'render_coupon_meta_box'), $this->coupon_post_type, 'normal', 'high');
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('adb_save_coupon_meta', 'adb_coupon_nonce');

        $affiliate_url = get_post_meta($post->ID, '_adb_affiliate_url', true);
        $expiry_date = get_post_meta($post->ID, '_adb_expiry_date', true);
        $coupon_code = get_post_meta($post->ID, '_adb_coupon_code', true);
        $clicks = get_post_meta($post->ID, '_adb_clicks', true);
        if (!$clicks) $clicks = 0;

        echo '<p><label for="adb_affiliate_url">' . __('Affiliate URL (required)', 'affiliate-deal-booster') . '</label><br />';
        echo '<input type="url" id="adb_affiliate_url" name="adb_affiliate_url" value="' . esc_attr($affiliate_url) . '" style="width:100%;" required></p>';

        echo '<p><label for="adb_coupon_code">' . __('Coupon Code (optional)', 'affiliate-deal-booster') . '</label><br />';
        echo '<input type="text" id="adb_coupon_code" name="adb_coupon_code" value="' . esc_attr($coupon_code) . '" style="width:100%;"></p>';

        echo '<p><label for="adb_expiry_date">' . __('Expiry Date (optional)', 'affiliate-deal-booster') . '</label><br />';
        echo '<input type="date" id="adb_expiry_date" name="adb_expiry_date" value="' . esc_attr($expiry_date) . '" style="width:200px;"></p>';

        echo '<p><strong>' . __('Click Count:', 'affiliate-deal-booster') . '</strong> ' . esc_html($clicks) . '</p>';
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['adb_coupon_nonce']) || !wp_verify_nonce($_POST['adb_coupon_nonce'], 'adb_save_coupon_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['adb_affiliate_url'])) {
            $url = esc_url_raw(trim($_POST['adb_affiliate_url']));
            update_post_meta($post_id, '_adb_affiliate_url', $url);
        }

        if (isset($_POST['adb_expiry_date'])) {
            $date = sanitize_text_field($_POST['adb_expiry_date']);
            update_post_meta($post_id, '_adb_expiry_date', $date);
        }

        if (isset($_POST['adb_coupon_code'])) {
            $code = sanitize_text_field($_POST['adb_coupon_code']);
            update_post_meta($post_id, '_adb_coupon_code', $code);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function shortcode_display_coupons($atts) {
        $args = array(
            'post_type' => $this->coupon_post_type,
            'posts_per_page' => 10,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_adb_expiry_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_adb_expiry_date',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        $coupons = get_posts($args);

        ob_start();
        if ($coupons) {
            echo '<div class="adb-coupon-list">';
            foreach ($coupons as $coupon) {
                $aff_url = get_post_meta($coupon->ID, '_adb_affiliate_url', true);
                $code = get_post_meta($coupon->ID, '_adb_coupon_code', true);
                $title = get_the_title($coupon);
                echo '<div class="adb-coupon-item">';
                echo '<h3>' . esc_html($title) . '</h3>';
                echo '<p>' . wp_trim_words($coupon->post_content, 20, '...') . '</p>';

                if ($code) {
                    echo '<p><strong>Coupon Code:</strong> <span class="adb-coupon-code" style="cursor:pointer;user-select:text;" onclick="navigator.clipboard.writeText(\'' . esc_js($code) . '\')">' . esc_html($code) . ' (click to copy)</span></p>';
                }

                $redirect_link = add_query_arg(array('adb_redirect_id' => $coupon->ID), home_url());
                echo '<p><a href="' . esc_url($redirect_link) . '" target="_blank" rel="nofollow noopener" class="adb-btn">Get Deal</a></p>';

                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No active affiliate deals found.</p>';
        }

        return ob_get_clean();
    }

    public function handle_redirect() {
        if (isset($_GET['adb_redirect_id'])) {
            $coupon_id = intval($_GET['adb_redirect_id']);
            $aff_url = get_post_meta($coupon_id, '_adb_affiliate_url', true);

            if ($aff_url) {
                // Increment click count
                $clicks = get_post_meta($coupon_id, '_adb_clicks', true);
                $clicks = ($clicks) ? intval($clicks) + 1 : 1;
                update_post_meta($coupon_id, '_adb_clicks', $clicks);

                // Redirect to affiliate URL
                wp_redirect($aff_url);
                exit;
            }
        }
    }

    public function schedule_cleanup() {
        if (!wp_next_scheduled('adb_cleanup_hook')) {
            wp_schedule_event(time(), 'daily', 'adb_cleanup_hook');
        }
    }

    public function cleanup_expired_coupons() {
        $today = date('Y-m-d');
        $args = array(
            'post_type' => $this->coupon_post_type,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_adb_expiry_date',
                    'value' => $today,
                    'type' => 'DATE',
                    'compare' => '<'
                )
            ),
            'fields' => 'ids'
        );
        $expired = get_posts($args);
        if ($expired) {
            foreach ($expired as $post_id) {
                wp_trash_post($post_id);
            }
        }
    }
}

new AffiliateDealBooster();

<?php
/*
Plugin Name: SmartCoupon Deals
Description: Manage and display exclusive affiliate coupon deals with advanced features.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartCoupon_Deals.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartCouponDeals {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post_coupon', array($this, 'save_coupon_meta'), 10, 2);
        add_shortcode('smartcoupon_list', array($this, 'display_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'append_redeem_button'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => 'Coupons',
            'singular_name' => 'Coupon',
            'add_new' => 'Add New Coupon',
            'add_new_item' => 'Add New Coupon',
            'edit_item' => 'Edit Coupon',
            'new_item' => 'New Coupon',
            'view_item' => 'View Coupon',
            'search_items' => 'Search Coupons',
            'not_found' => 'No coupons found',
            'not_found_in_trash' => 'No coupons found in Trash',
            'all_items' => 'All Coupons',
            'menu_name' => 'SmartCoupons',
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => 20,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-tickets-alt',
            'has_archive' => false,
        );

        register_post_type('coupon', $args);
    }

    public function add_coupon_meta_boxes() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'coupon_meta_box_html'), 'coupon', 'normal', 'high');
    }

    public function coupon_meta_box_html($post) {
        wp_nonce_field('save_coupon_data', 'coupon_nonce');

        $code = get_post_meta($post->ID, '_coupon_code', true);
        $affiliate_link = get_post_meta($post->ID, '_coupon_affiliate_link', true);
        $expiry_date = get_post_meta($post->ID, '_coupon_expiry_date', true);
        $discount = get_post_meta($post->ID, '_coupon_discount', true);

        echo '<p><label for="coupon_code">Coupon Code:</label><br />';
        echo '<input type="text" id="coupon_code" name="coupon_code" value="' . esc_attr($code) . '" style="width:100%;" /></p>';

        echo '<p><label for="coupon_affiliate_link">Affiliate Link (URL):</label><br />';
        echo '<input type="url" id="coupon_affiliate_link" name="coupon_affiliate_link" value="' . esc_attr($affiliate_link) . '" style="width:100%;" /></p>';

        echo '<p><label for="coupon_expiry_date">Expiry Date (YYYY-MM-DD):</label><br />';
        echo '<input type="date" id="coupon_expiry_date" name="coupon_expiry_date" value="' . esc_attr($expiry_date) . '" /></p>';

        echo '<p><label for="coupon_discount">Discount Description:</label><br />';
        echo '<input type="text" id="coupon_discount" name="coupon_discount" value="' . esc_attr($discount) . '" placeholder="e.g. 20% OFF" style="width:100%;" /></p>';

        echo '<p><em>Use the post content to describe the coupon details and restrictions.</em></p>';
    }

    public function save_coupon_meta($post_id, $post) {
        if (!isset($_POST['coupon_nonce']) || !wp_verify_nonce($_POST['coupon_nonce'], 'save_coupon_data')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if ($post->post_type != 'coupon') {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['coupon_code'])) {
            update_post_meta($post_id, '_coupon_code', sanitize_text_field($_POST['coupon_code']));
        }
        if (isset($_POST['coupon_affiliate_link'])) {
            update_post_meta($post_id, '_coupon_affiliate_link', esc_url_raw($_POST['coupon_affiliate_link']));
        }
        if (isset($_POST['coupon_expiry_date'])) {
            update_post_meta($post_id, '_coupon_expiry_date', sanitize_text_field($_POST['coupon_expiry_date']));
        }
        if (isset($_POST['coupon_discount'])) {
            update_post_meta($post_id, '_coupon_discount', sanitize_text_field($_POST['coupon_discount']));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smartcoupon-style', plugin_dir_url(__FILE__) . 'smartcoupon-style.css');
    }

    public function display_coupons_shortcode($atts) {
        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => 10,
            'meta_query' => array(
                array(
                    'key' => '_coupon_expiry_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_coupon_expiry_date',
            'order' => 'ASC'
        );
        $coupons = new WP_Query($args);

        if (!$coupons->have_posts()) {
            return '<p>No active coupon deals available.</p>';
        }

        $output = '<div class="smartcoupon-list">';

        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_coupon_code', true);
            $link = get_post_meta(get_the_ID(), '_coupon_affiliate_link', true);
            $expiry = get_post_meta(get_the_ID(), '_coupon_expiry_date', true);
            $discount = get_post_meta(get_the_ID(), '_coupon_discount', true);

            $content = get_the_content();

            $output .= '<div class="smartcoupon-item">';
            $output .= '<h3>' . get_the_title() . '</h3>';
            if ($discount) $output .= '<p><strong>Discount:</strong> ' . esc_html($discount) . '</p>';
            if ($expiry) $output .= '<p><em>Expires on: ' . esc_html($expiry) . '</em></p>';
            $output .= '<div>' . wpautop($content) . '</div>';
            if ($link && $code) {
                $output .= '<p><button class="smartcoupon-redeem-button" data-code="' . esc_attr($code) . '" data-link="' . esc_url($link) . '">Redeem Coupon</button></p>';
            } elseif ($link) {
                $output .= '<p><a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener noreferrer" class="smartcoupon-link">Visit Deal</a></p>';
            }
            $output .= '</div>';
        }

        wp_reset_postdata();

        $output .= '</div>';

        $output .= '<script>document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".smartcoupon-redeem-button").forEach(function(button) {
                button.addEventListener("click", function() {
                    var code = this.getAttribute("data-code");
                    var link = this.getAttribute("data-link");
                    navigator.clipboard.writeText(code).then(function() {
                        alert("Coupon code ' + code + ' copied! Redirecting...");
                        window.open(link, "_blank");
                    }, function() {
                        alert("Failed to copy coupon code. Please copy manually: " + code);
                        window.open(link, "_blank");
                    });
                });
            });
        });</script>';

        return $output;
    }

    public function append_redeem_button($content) {
        if (is_singular('coupon')) {
            $code = get_post_meta(get_the_ID(), '_coupon_code', true);
            $link = get_post_meta(get_the_ID(), '_coupon_affiliate_link', true);
            if ($code && $link) {
                $button = '<p><button class="smartcoupon-redeem-button" data-code="' . esc_attr($code) . '" data-link="' . esc_url($link) . '">Redeem Coupon</button></p>';
                $content .= $button;
            }
        }
        return $content;
    }
}

new SmartCouponDeals();
<?php
/*
Plugin Name: Affiliate Coupon Hub
Plugin URI: https://example.com/affiliate-coupon-hub
Description: Create and display custom coupons and deals with affiliate tracking to increase affiliate earnings.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Hub.php
*/

if (!defined('ABSPATH')) exit;

class Affiliate_Coupon_Hub {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_shortcode('affiliate_coupons', array($this, 'display_coupons_shortcode'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post', array($this, 'save_coupon_meta'));    
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => 'Affiliate Coupons',
            'singular_name' => 'Affiliate Coupon',
            'add_new' => 'Add New Coupon',
            'add_new_item' => 'Add New Affiliate Coupon',
            'edit_item' => 'Edit Affiliate Coupon',
            'new_item' => 'New Affiliate Coupon',
            'view_item' => 'View Coupon',
            'search_items' => 'Search Coupons',
            'not_found' => 'No coupons found',
            'not_found_in_trash' => 'No coupons found in Trash',
            'menu_name' => 'Affiliate Coupons'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'menu_position' => 20,
            'menu_icon' => 'dashicons-tickets-alt',
            'show_in_rest' => true
        );

        register_post_type('affiliate_coupon', $args);
    }

    public function add_coupon_meta_boxes() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'render_coupon_meta_box'), 'affiliate_coupon', 'normal', 'high');
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('save_coupon_meta', 'coupon_meta_nonce');
        $affiliate_link = get_post_meta($post->ID, '_affiliate_link', true);
        $expiration_date = get_post_meta($post->ID, '_expiration_date', true);
        $discount_code = get_post_meta($post->ID, '_discount_code', true);

        echo '<p><label for="affiliate_link">Affiliate URL:</label><br />';
        echo '<input type="url" id="affiliate_link" name="affiliate_link" value="' . esc_attr($affiliate_link) . '" style="width:100%;" placeholder="https://affiliate.example.com/?ref=yourid" /></p>';

        echo '<p><label for="discount_code">Discount Code (optional):</label><br />';
        echo '<input type="text" id="discount_code" name="discount_code" value="' . esc_attr($discount_code) . '" style="width:100%;" /></p>';

        echo '<p><label for="expiration_date">Expiration Date (optional):</label><br />';
        echo '<input type="date" id="expiration_date" name="expiration_date" value="' . esc_attr($expiration_date) . '" /></p>';
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['coupon_meta_nonce']) || !wp_verify_nonce($_POST['coupon_meta_nonce'], 'save_coupon_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['affiliate_link'])) {
            update_post_meta($post_id, '_affiliate_link', esc_url_raw($_POST['affiliate_link']));
        }

        if (isset($_POST['expiration_date'])) {
            update_post_meta($post_id, '_expiration_date', sanitize_text_field($_POST['expiration_date']));
        }

        if (isset($_POST['discount_code'])) {
            update_post_meta($post_id, '_discount_code', sanitize_text_field($_POST['discount_code']));
        }
    }

    public function display_coupons_shortcode($atts) {
        $args = array('post_type' => 'affiliate_coupon', 'posts_per_page' => -1, 'post_status' => 'publish');
        $coupons = get_posts($args);
        if (empty($coupons)) {
            return '<p>No coupons available currently.</p>';
        }

        $output = '<div class="affiliate-coupon-hub">';

        foreach ($coupons as $coupon) {
            $link = get_post_meta($coupon->ID, '_affiliate_link', true);
            $expiration = get_post_meta($coupon->ID, '_expiration_date', true);
            $discount_code = get_post_meta($coupon->ID, '_discount_code', true);

            // Check expiration
            if ($expiration && strtotime($expiration) < time()) continue;

            $output .= '<div class="coupon-item" style="border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:4px;">';
            $output .= '<h3>' . esc_html($coupon->post_title) . '</h3>';
            $output .= '<div class="description">' . wp_kses_post(wpautop($coupon->post_content)) . '</div>';

            if ($discount_code) {
                $output .= '<p><strong>Use code:</strong> <code>' . esc_html($discount_code) . '</code></p>';
            }

            if ($link) {
                $output .= '<p><a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="button" style="display:inline-block;margin-top:10px;padding:10px 20px;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px;">Redeem Offer</a></p>';
            }
            if ($expiration) {
                $output .= '<small>Expires on ' . esc_html(date('F j, Y', strtotime($expiration))) . '</small>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    public function enqueue_styles() {
        wp_enqueue_style('affiliate-coupon-hub-styles', plugin_dir_url(__FILE__) . 'styles.css');
    }
}

new Affiliate_Coupon_Hub();

// Fallback style for embedded CSS in case styles.css file missing
add_action('wp_head', function() {
    echo '<style>.affiliate-coupon-hub .coupon-item{border:1px solid #ddd;padding:15px;margin-bottom:15px;border-radius:4px;}.affiliate-coupon-hub .button{display:inline-block;margin-top:10px;padding:10px 20px;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px;}</style>';
});
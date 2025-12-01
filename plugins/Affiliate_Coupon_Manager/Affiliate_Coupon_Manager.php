<?php
/*
Plugin Name: Affiliate Coupon Manager
Description: Create and display affiliate coupons with tracking and analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Manager.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

class Affiliate_Coupon_Manager {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_shortcode('affiliate_coupons', array($this, 'display_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('template_redirect', array($this, 'track_coupon_click'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post_coupon', array($this, 'save_coupon_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
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
            'menu_name' => 'Coupons'
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-tag',
            'supports' => array('title', 'editor'),
            'rewrite' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'edit_coupon',
                'read_post' => 'read_coupon',
                'delete_post' => 'delete_coupon',
                'edit_posts' => 'edit_coupons',
                'edit_others_posts' => 'edit_others_coupons',
                'publish_posts' => 'publish_coupons',
                'read_private_posts' => 'read_private_coupons',
            ),
            'map_meta_cap' => true,
        );
        register_post_type('coupon', $args);
    }

    public function add_coupon_meta_boxes() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'coupon_meta_box_callback'), 'coupon', 'normal', 'default');
    }

    public function coupon_meta_box_callback($post) {
        wp_nonce_field('save_coupon', 'coupon_nonce');
        $affiliate_url = get_post_meta($post->ID, '_affiliate_url', true);
        $code = get_post_meta($post->ID, '_coupon_code', true);
        $usage_count = get_post_meta($post->ID, '_usage_count', true);
        if (!$usage_count) $usage_count = 0;
        ?>
        <p><label for="affiliate_url">Affiliate Link URL:</label><br>
        <input type="url" id="affiliate_url" name="affiliate_url" value="<?php echo esc_attr($affiliate_url); ?>" style="width:100%;" required></p>

        <p><label for="coupon_code">Coupon Code:</label><br>
        <input type="text" id="coupon_code" name="coupon_code" value="<?php echo esc_attr($code); ?>" style="width:100%;" required></p>

        <p><strong>Usage Count:</strong> <?php echo intval($usage_count); ?></p>
        <?php
    }

    public function save_coupon_meta($post_id, $post) {
        if (!isset($_POST['coupon_nonce']) || !wp_verify_nonce($_POST['coupon_nonce'], 'save_coupon')) {
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
        if (isset($_POST['affiliate_url'])) {
            update_post_meta($post_id, '_affiliate_url', esc_url_raw($_POST['affiliate_url']));
        }
        if (isset($_POST['coupon_code'])) {
            update_post_meta($post_id, '_coupon_code', sanitize_text_field($_POST['coupon_code']));
        }
    }

    public function enqueue_styles() {
        wp_register_style('affiliate_coupon_manager_style', plugins_url('style.css', __FILE__));
        wp_enqueue_style('affiliate_coupon_manager_style');
    }

    public function display_coupons_shortcode($atts) {
        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        $coupons = get_posts($args);

        if (!$coupons) {
            return '<p>No coupons available at the moment.</p>';
        }

        $output = '<div class="affiliate-coupon-list">';
        foreach ($coupons as $coupon) {
            $code = get_post_meta($coupon->ID, '_coupon_code', true);
            $affiliate_url = get_post_meta($coupon->ID, '_affiliate_url', true);
            $title = esc_html(get_the_title($coupon));
            $desc = wp_trim_words($coupon->post_content, 20, '...');
            $output .= '<div class="affiliate-coupon">
                <h3>' . $title . '</h3>
                <p>' . esc_html($desc) . '</p>
                <p><strong>Coupon Code:</strong> <span class="coupon-code">' . esc_html($code) . '</span></p>
                <p><a href="' . esc_url(add_query_arg(array('aff_coupon' => $coupon->ID), home_url('/'))) . '" target="_blank" rel="nofollow noopener">Use Coupon</a></p>
                </div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function track_coupon_click() {
        if (!isset($_GET['aff_coupon'])) {
            return;
        }
        $coupon_id = intval($_GET['aff_coupon']);
        $affiliate_url = get_post_meta($coupon_id, '_affiliate_url', true);
        if ($affiliate_url) {
            $usage_count = get_post_meta($coupon_id, '_usage_count', true);
            $usage_count = $usage_count ? $usage_count + 1 : 1;
            update_post_meta($coupon_id, '_usage_count', $usage_count);
            wp_redirect($affiliate_url);
            exit;
        }
    }

    public function admin_scripts($hook) {
        if ('post.php' != $hook && 'post-new.php' != $hook) {
            return;
        }
        global $post;
        if ($post->post_type != 'coupon') {
            return;
        }
        wp_enqueue_style('admin-coupon-style', plugins_url('admin-style.css', __FILE__));
    }
}

new Affiliate_Coupon_Manager();

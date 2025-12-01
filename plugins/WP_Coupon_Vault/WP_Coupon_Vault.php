/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Plugin URI: https://example.com/wp-coupon-vault
 * Description: Create, manage, and display exclusive coupons and deals for your audience.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type: Coupon
function wcv_register_coupon_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Coupons',
        'supports' => array('title', 'editor', 'custom-fields'),
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'coupons'),
        'menu_icon' => 'dashicons-tag'
    );
    register_post_type('wcv_coupon', $args);
}
add_action('init', 'wcv_register_coupon_post_type');

// Shortcode to display coupons
function wcv_coupon_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 5,
        'category' => ''
    ), $atts, 'wcv_coupons');

    $args = array(
        'post_type' => 'wcv_coupon',
        'posts_per_page' => $atts['limit'],
        'tax_query' => !empty($atts['category']) ? array(
            array(
                'taxonomy' => 'wcv_coupon_category',
                'field' => 'slug',
                'terms' => $atts['category']
            )
        ) : array()
    );

    $coupons = new WP_Query($args);
    $output = '<div class="wcv-coupons">';
    if ($coupons->have_posts()) {
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_wcv_coupon_code', true);
            $url = get_post_meta(get_the_ID(), '_wcv_coupon_url', true);
            $output .= '<div class="wcv-coupon">
                <h3>' . get_the_title() . '</h3>
                <p>' . get_the_content() . '</p>
                <p><strong>Coupon Code:</strong> ' . esc_html($code) . '</p>
                <a href="' . esc_url($url) . '" target="_blank" class="wcv-coupon-btn">Get Deal</a>
            </div>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>No coupons found.</p>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('wcv_coupons', 'wcv_coupon_shortcode');

// Add meta boxes for coupon code and URL
function wcv_add_coupon_meta_boxes() {
    add_meta_box('wcv_coupon_meta', 'Coupon Details', 'wcv_coupon_meta_callback', 'wcv_coupon', 'normal', 'high');
}
add_action('add_meta_boxes', 'wcv_add_coupon_meta_boxes');

function wcv_coupon_meta_callback($post) {
    wp_nonce_field('wcv_save_coupon_meta', 'wcv_coupon_meta_nonce');
    $code = get_post_meta($post->ID, '_wcv_coupon_code', true);
    $url = get_post_meta($post->ID, '_wcv_coupon_url', true);
    echo '<p><label for="wcv_coupon_code">Coupon Code:</label><br>
        <input type="text" id="wcv_coupon_code" name="wcv_coupon_code" value="' . esc_attr($code) . '" style="width:100%"></p>';
    echo '<p><label for="wcv_coupon_url">Affiliate URL:</label><br>
        <input type="url" id="wcv_coupon_url" name="wcv_coupon_url" value="' . esc_attr($url) . '" style="width:100%"></p>';
}

function wcv_save_coupon_meta($post_id) {
    if (!isset($_POST['wcv_coupon_meta_nonce']) || !wp_verify_nonce($_POST['wcv_coupon_meta_nonce'], 'wcv_save_coupon_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['wcv_coupon_code'])) {
        update_post_meta($post_id, '_wcv_coupon_code', sanitize_text_field($_POST['wcv_coupon_code']));
    }
    if (isset($_POST['wcv_coupon_url'])) {
        update_post_meta($post_id, '_wcv_coupon_url', esc_url_raw($_POST['wcv_coupon_url']));
    }
}
add_action('save_post', 'wcv_save_coupon_meta');

// Enqueue styles
function wcv_enqueue_styles() {
    wp_enqueue_style('wcv-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'wcv_enqueue_styles');

// Create default coupon category taxonomy
function wcv_create_coupon_category() {
    register_taxonomy('wcv_coupon_category', 'wcv_coupon', array(
        'label' => 'Coupon Categories',
        'rewrite' => array('slug' => 'coupon-category'),
        'hierarchical' => true,
    ));
}
add_action('init', 'wcv_create_coupon_category');

// Add sample coupon on activation
function wcv_activation() {
    if (!get_option('wcv_sample_coupon_added')) {
        $coupon = array(
            'post_title' => 'Sample Coupon',
            'post_content' => 'Get 10% off your first purchase!',
            'post_status' => 'publish',
            'post_type' => 'wcv_coupon'
        );
        $coupon_id = wp_insert_post($coupon);
        update_post_meta($coupon_id, '_wcv_coupon_code', 'SAVE10');
        update_post_meta($coupon_id, '_wcv_coupon_url', 'https://example.com');
        update_option('wcv_sample_coupon_added', true);
    }
}
register_activation_hook(__FILE__, 'wcv_activation');

// Add admin menu for settings
function wcv_admin_menu() {
    add_options_page('WP Coupon Vault Settings', 'Coupon Vault', 'manage_options', 'wcv-settings', 'wcv_settings_page');
}
add_action('admin_menu', 'wcv_admin_menu');

function wcv_settings_page() {
    echo '<div class="wrap"><h1>WP Coupon Vault Settings</h1><p>Settings for WP Coupon Vault plugin.</p></div>';
}

// Add premium upsell notice in admin
function wcv_admin_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Coupon Vault Pro</strong> for advanced analytics, bulk import, and custom branding!</p></div>';
    }
}
add_action('admin_notices', 'wcv_admin_notice');

// Add custom CSS
function wcv_add_custom_css() {
    echo '<style>
        .wcv-coupons { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .wcv-coupon { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .wcv-coupon-btn { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>';
}
add_action('wp_head', 'wcv_add_custom_css');
?>
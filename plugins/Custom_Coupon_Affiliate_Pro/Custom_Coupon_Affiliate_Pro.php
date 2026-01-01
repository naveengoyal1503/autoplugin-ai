/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Affiliate Pro
 * Plugin URI: https://example.com/coupon-pro
 * Description: Generate personalized affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-coupon-pro
 */

if (!defined('ABSPATH')) exit;

class CustomCouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ccap_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }
        wp_register_post_type('ccap_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-cart'
        ));
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=ccap_coupon', 'Coupon Stats', 'Stats', 'manage_options', 'ccap-stats', array($this, 'stats_page'));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ccap_coupon') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('ccap-admin', plugin_dir_url(__FILE__) . 'admin.css');
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon = get_post($atts['id']);
        if (!$coupon || $coupon->post_type !== 'ccap_coupon') return '';

        $code = get_post_meta($coupon->ID, 'coupon_code', true);
        $aff_link = get_post_meta($coupon->ID, 'affiliate_link', true);
        $uses = get_post_meta($coupon->ID, 'uses', true) ?: 0;
        $max_uses = get_post_meta($coupon->ID, 'max_uses', true) ?: 999;

        if ($uses >= $max_uses) return '<p>Coupon expired!</p>';

        $track_id = uniqid('ccap_');
        update_post_meta($coupon->ID, 'uses', $uses + 1);

        $click_url = add_query_arg(array('ccap_coupon' => $coupon->ID, 'track' => $track_id), $aff_link);

        ob_start();
        echo '<div class="ccap-coupon" style="border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9;">
                <h3>' . esc_html($coupon->post_title) . '</h3>
                <p><strong>Code:</strong> <span class="coupon-code">' . esc_html($code) . '</span></p>
                <p>Used: ' . $uses . '/' . $max_uses . '</p>
                <a href="' . esc_url($click_url) . '" class="button button-large" target="_blank" onclick="ccapTrackClick(' . $coupon->ID . ', \'' . $track_id . '\')">Redeem Now (Affiliate)</a>
              </div>';
        return ob_get_clean();
    }

    public function stats_page() {
        global $wpdb;
        $stats = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key LIKE '_ccap_clicks_%'");
        echo '<div class="wrap"><h1>Coupon Stats</h1><ul>';
        foreach ($stats as $stat) {
            echo '<li>' . esc_html($stat->meta_key) . ': ' . esc_html($stat->meta_value) . '</li>';
        }
        echo '</ul></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new CustomCouponAffiliatePro();

// Pro upsell nag
function ccap_pro_nag() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Coupon Pro</strong> for unlimited coupons, advanced analytics & custom codes! <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a></p></div>';
}
add_action('admin_notices', 'ccap_pro_nag');

// AJAX track clicks
function ccap_track_click() {
    if (!wp_verify_nonce($_POST['nonce'], 'ccap_nonce')) die();
    $coupon_id = intval($_POST['coupon_id']);
    $track_id = sanitize_text_field($_POST['track_id']);
    update_post_meta($coupon_id, '_ccap_clicks_' . $track_id, current_time('mysql'));
    wp_die('Tracked');
}
add_action('wp_ajax_ccap_track_click', 'ccap_track_click');

// Enqueue frontend script
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('jquery', 'function ccapTrackClick(id, track) { $.post(ajaxurl, {action: "ccap_track_click", coupon_id: id, track_id: track, nonce: "' . wp_create_nonce('ccap_nonce') . '"}); }');
});
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon management for affiliate marketing. Generate, track, and display exclusive coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('scv_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_scv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('scv_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-script', plugin_dir_url(__FILE__) . 'scv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scv-script', 'scv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('Smart Coupon Vault', 'Coupons', 'manage_options', 'scv-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['scv_save'])) {
            update_option('scv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('scv_coupons', '[]');
        echo '<div class="wrap"><h1>Smart Coupon Vault</h1><form method="post">';
        echo '<textarea name="coupons" rows="10" cols="80">' . esc_textarea($coupons) . '</textarea><br>';
        echo '<p>Format: JSON array e.g. [{"code":"SAVE20","desc":"20% off","afflink":"https://aff.link","expires":"2026-12-31"}]</p>';
        echo '<p><strong>Pro:</strong> AI generation, analytics, unlimited coupons. <a href="#" onclick="alert(\'Upgrade to Pro for $49/year\')">Get Pro</a></p>';
        echo '<input type="submit" name="scv_save" class="button-primary" value="Save Coupons">';
        echo '</form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('scv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';
        $coupon = $coupons[$atts['id']];
        $rand = wp_rand(1, 9999);
        ob_start();
        echo '<div class="scv-coupon" data-id="' . $atts['id'] . '" data-rand="' . $rand . '">';
        echo '<h3>' . esc_html($coupon['desc']) . '</h3>';
        echo '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
        if (isset($coupon['expires'])) echo '<p>Expires: ' . $coupon['expires'] . '</p>';
        echo '<a href="#" class="scv-btn button">Get Deal</a>';
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        $id = intval($_POST['id']);
        $coupons = json_decode(get_option('scv_coupons', '[]'), true);
        if (isset($coupons[$id]['afflink'])) {
            update_option('scv_clicks_' . $id, (get_option('scv_clicks_' . $id, 0) + 1));
            wp_redirect($coupons[$id]['afflink']);
            exit;
        }
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Coupon Vault Pro</strong> for AI coupon generation and analytics! <a href="#">Learn more</a></p></div>';
    }

    public function activate() {
        if (!get_option('scv_coupons')) {
            update_option('scv_coupons', json_encode(array(
                array('code' => 'WELCOME10', 'desc' => '10% off first purchase', 'afflink' => '#', 'expires' => '2026-06-30')
            )));
        }
    }
}

new SmartCouponVault();

// Inline JS
add_action('wp_head', function() {
    echo '<style>.scv-coupon {border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;}.scv-btn {background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;}</style>';
    echo '<script>jQuery(document).ready(function($) { $(".scv-btn").click(function(e) { e.preventDefault(); $.post(scv_ajax.ajax_url, {action: "scv_track_click", id: $(this).closest(".scv-coupon").data("id")}); }); });</script>';
});
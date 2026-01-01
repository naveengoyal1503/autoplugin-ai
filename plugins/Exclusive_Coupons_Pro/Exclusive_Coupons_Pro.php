/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and expiration for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('ecp-admin-style');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('ecp_coupons', '');
        echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1><form method="post"><textarea name="coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea><p>Format: Code|AffiliateLink|Description|Expiry Days</p><p class="submit"><input type="submit" name="submit" class="button-primary" value="Save"></p></form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode('\n', get_option('ecp_coupons', ''));
        foreach ($coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) >= 4 && $parts === $atts['id']) {
                $expiry = strtotime('+' . $parts[3] . ' days');
                if (time() > $expiry) {
                    return '<p class="expired-coupon">Coupon expired!</p>';
                }
                $clicks = get_option('ecp_clicks_' . $parts, 0) + 1;
                update_option('ecp_clicks_' . $parts, $clicks);
                return '<div class="exclusive-coupon"><h3>' . esc_html($parts[2]) . '</h3><p>Code: <strong>' . esc_html($parts) . '</strong></p><a href="' . esc_url($parts[1]) . '" target="_blank" class="coupon-btn">Get Deal (Tracked: ' . $clicks . ')</a></div>';
            }
        }
        return '';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new ExclusiveCouponsPro();

// Pro upsell notice
function ecp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons, analytics dashboard, and auto-generation! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ecp_pro_notice');

// Basic CSS
add_action('wp_head', function() {
    echo '<style>.exclusive-coupon {border: 2px solid #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;}.coupon-btn {background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;}.expired-coupon {color: red; font-weight: bold;}</style>';
});
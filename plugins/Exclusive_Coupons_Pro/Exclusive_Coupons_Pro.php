/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('ecp-admin-style');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('ecp_coupons', '');
        echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1><form method="post"><textarea name="coupons" rows="20" cols="80" placeholder="Code|Description|Affiliate Link|Expiry Date (YYYY-MM-DD)">' . esc_textarea($coupons) . '</textarea><p>Format each line: CODE|Description|Affiliate URL|Expiry (YYYY-MM-DD)</p><input type="submit" name="submit" value="Save" class="button-primary"></form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupons = explode('\n', get_option('ecp_coupons', ''));
        foreach ($coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) >= 4 && $parts === $atts['code']) {
                if (strtotime($parts[3]) > current_time('timestamp')) {
                    return '<div class="exclusive-coupon"><h3><strong>' . esc_html($parts) . '</strong></h3><p>' . esc_html($parts[1]) . '</p><a href="' . esc_url($parts[2]) . '" class="button" target="_blank">Get Deal Now</a></div>';
                }
            }
        }
        return '<p>This coupon has expired or is invalid.</p>';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', '');
        }
    }
}

new ExclusiveCouponsPro();

/* Premium Upsell Notice */
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('ECP_PREMIUM')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro Premium</strong>: Unlimited coupons, analytics, auto-expiration, and more! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }
});

/* Basic CSS */
add_action('wp_head', function() {
    echo '<style>.exclusive-coupon {border: 2px solid #0073aa; padding: 20px; background: #f9f9f9; text-align: center; margin: 20px 0;}.exclusive-coupon .button {background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;}</style>';
});

/* Admin CSS */
function ecp_admin_styles() {
    echo '<style>.wrap textarea {width: 100%; max-width: 800px;}</style>';
}
add_action('admin_head', 'ecp_admin_styles');
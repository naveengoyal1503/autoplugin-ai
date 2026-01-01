/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive, personalized coupon codes for your WordPress site to boost affiliate conversions and reader loyalty.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('ecp-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0');
            wp_enqueue_style('ecp-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            $this->generate_coupon();
        }
        $coupons = get_option('ecp_coupons', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function generate_coupon() {
        $code = strtoupper(wp_generate_uuid4());
        $code = substr($code, 0, 12);
        $description = sanitize_text_field($_POST['description']);
        $affiliate_url = esc_url_raw($_POST['affiliate_url']);
        $discount = sanitize_text_field($_POST['discount']);
        $expiry = sanitize_text_field($_POST['expiry']);

        $coupons = get_option('ecp_coupons', array());
        $coupons[] = array(
            'code' => $code,
            'description' => $description,
            'affiliate_url' => $affiliate_url,
            'discount' => $discount,
            'expiry' => $expiry,
            'uses' => 0,
            'created' => current_time('mysql')
        );
        update_option('ecp_coupons', $coupons);
        echo '<div class="notice notice-success"><p>Coupon generated: <strong>' . $code . '</strong></p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        $coupons = get_option('ecp_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '';
        }

        $coupon = $coupons[$atts['id']];
        $today = current_time('timestamp');
        $expiry_time = strtotime($coupon['expiry']);

        if ($today > $expiry_time) {
            return '<p class="ecp-expired">Coupon expired.</p>';
        }

        $output = '<div class="ecp-coupon-box">';
        $output .= '<h3>' . esc_html($coupon['description']) . '</h3>';
        $output .= '<p><strong>Code:</strong> <span class="ecp-code">' . $coupon['code'] . '</span></p>';
        $output .= '<p><strong>Discount:</strong> ' . esc_html($coupon['discount']) . '% OFF</p>';
        $output .= '<p><a href="' . esc_url($coupon['affiliate_url']) . '" target="_blank" class="button ecp-use-btn">Use Coupon</a></p>';
        $output .= '</div>';

        return $output;
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            add_option('ecp_coupons', array());
        }
    }
}

new ExclusiveCouponsPro();

// Pro upsell notice
function ecp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics, auto-expiry, and custom branding. <a href="https://example.com/pro" target="_blank">Upgrade now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ecp_pro_notice');

// Enqueue frontend styles
function ecp_styles() {
    wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'ecp_styles');
?>
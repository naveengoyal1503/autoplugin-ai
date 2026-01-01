/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('ecp_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format: {"brand":"code","brand2":"code2"})</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'save_coupon'); ?>
            </form>
            <p>Use shortcode: <code>[exclusive_coupon brand="Brand Name"]</code></p>
            <h2>Pro Features (Upgrade for $49/year)</h2>
            <ul>
                <li>Unlimited coupons</li>
                <li>Analytics & tracking</li>
                <li>Auto-expiration</li>
                <li>Brand API integrations</li>
            </ul>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons = get_option('ecp_coupons', array());
        if (isset($coupons[$atts['brand']])) {
            $link = isset($atts['link']) ? $atts['link'] : site_url();
            return '<div class="exclusive-coupon"><strong>Exclusive Deal:</strong> Use code <code>' . esc_html($coupons[$atts['brand']]) . '</code> at <a href="' . esc_url($link) . '" target="_blank">' . esc_html($atts['brand']) . '</a> for savings! <small>Tracked affiliate link</small></div>';
        }
        return 'Coupon not found.';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array('ExampleBrand' => 'SAVE20'));
        }
    }
}

new ExclusiveCouponsPro();

// Pro upsell notice
function ecp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro</strong> for unlimited features: <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ecp_pro_notice');

// Basic CSS
add_action('wp_head', function() {
    echo '<style>.exclusive-coupon { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0; }</style>';
});

// Track clicks (basic)
add_action('wp_ajax_ecp_track', 'ecp_track_click');
add_action('wp_ajax_nopriv_ecp_track', 'ecp_track_click');
function ecp_track_click() {
    // Pro feature: Log click
    wp_die();
}

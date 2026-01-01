/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and monetize your WordPress site.
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
        wp_register_script('ecp-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
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
                        <th>Coupons (JSON format: {"name":"Code","code":"DISCOUNT20","afflink":"https://aff.link","desc":"20% off"})</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons'); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[exclusive_coupon id="name"]</code> to display a coupon.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ecp_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $click_id = uniqid();
        return '<div class="exclusive-coupon" style="border:2px solid #007cba; padding:20px; background:#f9f9f9; text-align:center;">
                    <h3>' . esc_html($coupon['name']) . '</h3>
                    <p>' . esc_html($coupon['desc']) . '</p>
                    <div style="font-size:24px; color:#007cba; margin:10px 0;">' . esc_html($coupon['code']) . '</div>
                    <a href="' . esc_url($coupon['afflink']) . '?ref=' . $click_id . '" class="button button-primary" style="padding:10px 20px; font-size:16px;" target="_blank">Get Deal Now</a>
                </div>';
    }

    public function activate() {
        add_option('ecp_coupons', array(
            'example' => array(
                'name' => 'Example Deal',
                'code' => 'WELCOME20',
                'afflink' => 'https://example.com/affiliate',
                'desc' => '20% off your first purchase'
            )
        ));
    }
}

new ExclusiveCouponsPro();

/* Premium Upsell Notice */
add_action('admin_notices', function() {
    if (!get_option('ecp_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons, analytics, and auto-expiry! <a href="https://example.com/pro">Get Pro ($49/year)</a> | <a href="?ecp_dismiss=1">Dismiss</a></p></div>';
    }
});

if (isset($_GET['ecp_dismiss'])) {
    update_option('ecp_pro_dismissed', 1);
}
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/custom-affiliate-coupons-pro
 * Description: Generate and manage exclusive custom coupons for affiliate marketing, boosting conversions with personalized discount codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-affiliate-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomAffiliateCouponsPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('cac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('cac-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('cac-admin-style');
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Coupons',
            'Affiliate Coupons',
            'manage_options',
            'custom-affiliate-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['cac_submit'])) {
            update_option('cac_coupons', sanitize_textarea_field($_POST['cac_coupons']));
        }
        $coupons = get_option('cac_coupons', '');
        ?>
        <div class="wrap">
            <h1>Custom Affiliate Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format)</th>
                        <td>
                            <textarea name="cac_coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                            <p class="description">Enter coupons as JSON: [{ "code": "SAVE10", "afflink": "https://aff.link", "desc": "10% off" }]</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[cac_coupon id="1"]</code> to display a coupon. IDs start from 0.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('cac_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $click_id = uniqid();
        return '<div class="cac-coupon" style="border:1px solid #ccc; padding:20px; margin:10px 0; background:#f9f9f9;">
                    <h3>' . esc_html($coupon['desc']) . '</h3>
                    <p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>
                    <a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="button button-primary" onclick="cacTrackClick(' . $atts['id'] . ', \'' . $click_id . '\');">Get Deal</a>
                </div>
                <script>
                function cacTrackClick(id, clickid) {
                    fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: "action=cac_track_click&id=" + id + "&clickid=" + clickid
                    });
                }
                </script>';
    }

    public function activate() {
        if (!get_option('cac_coupons')) {
            update_option('cac_coupons', '[]');
        }
    }
}

new CustomAffiliateCouponsPro();

// Pro upgrade nag
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('CAC_PRO')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Affiliate Coupons Pro</strong> for unlimited coupons, analytics, and auto-expiration! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
});
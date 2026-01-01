/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Partner_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Partner Pro
 * Plugin URI: https://example.com/coupon-partner-pro
 * Description: Create, manage, and display exclusive custom coupons for affiliate partnerships to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomCouponPartnerPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('cpp_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));
        }
    }

    public function enqueue_admin($hook) {
        if ($hook !== 'toplevel_page_cpp-admin') return;
        wp_enqueue_script('jquery');
        wp_enqueue_style('cpp-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0');
    }

    public function admin_menu() {
        add_menu_page('Coupon Partner Pro', 'Coupons', 'manage_options', 'cpp-admin', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('cpp_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('cpp_coupons', array());
        ?>
        <div class="wrap">
            <h1>Custom Coupon Partner Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="coupons[code]" value="<?php echo esc_attr($coupons['code'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="coupons[discount]" value="<?php echo esc_attr($coupons['discount'] ?? ''); ?>" /> % off</td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="coupons[link]" style="width:100%;" value="<?php echo esc_attr($coupons['link'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Brand</th>
                        <td><input type="text" name="coupons[brand]" value="<?php echo esc_attr($coupons['brand'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Expiry Date</th>
                        <td><input type="date" name="coupons[expiry]" value="<?php echo esc_attr($coupons['expiry'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="save_coupon" class="button-primary" value="Save Coupon" /></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[cpp_coupon]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = get_option('cpp_coupons', array());
        if (empty($coupons['code']) || (isset($coupons['expiry']) && strtotime($coupons['expiry']) < current_time('timestamp'))) {
            return '<p class="cpp-expired">Coupon expired or not set.</p>';
        }
        $style = 'background:#fff; border:2px dashed #0073aa; padding:20px; text-align:center; margin:20px 0; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1);';
        return '<div style="' . $style . '">
                    <h3>Exclusive Deal: <strong>' . esc_html($coupons['brand'] ?? 'Partner') . '</strong></h3>
                    <div style="font-size:48px; color:#0073aa; font-weight:bold;">' . esc_html($coupons['code'] ?? '') . '</div>
                    <p><strong>' . esc_html($coupons['discount'] ?? '') . '% OFF</strong></p>
                    <a href="' . esc_url($coupons['link'] ?? '') . '" target="_blank" class="button button-large" style="background:#0073aa; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">Shop Now & Save</a>
                    <p style="font-size:12px; margin-top:10px;">Limited time offer. Expires ' . esc_html($coupons['expiry'] ?? '') . '</p>
                </div>';
    }

    public function activate() {
        if (!get_option('cpp_coupons')) {
            update_option('cpp_coupons', array());
        }
    }
}

new CustomCouponPartnerPro();

// Premium upsell notice (free version)
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Custom Coupon Partner Pro:</strong> Upgrade to Pro for unlimited coupons, analytics, and auto-expiry! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
});
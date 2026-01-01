/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Affiliate Pro
 * Plugin URI: https://example.com/custom-coupon-pro
 * Description: Generate exclusive custom coupons for affiliate marketing with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-coupon-pro
 */

if (!defined('ABSPATH')) exit;

class CustomCouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ccap_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('custom-coupon-pro');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Custom Coupons',
            'Coupons Pro',
            'manage_options',
            'custom-coupons',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_scripts($hook) {
        if ('toplevel_page_custom-coupons' !== $hook) return;
        wp_enqueue_script('jquery');
    }

    public function admin_page() {
        if (isset($_POST['ccap_save'])) {
            update_option('ccap_coupons', sanitize_text_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('ccap_coupons', 'SAVE10-YourBrand');
        ?>
        <div class="wrap">
            <h1>Custom Coupon Generator</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="coupons" value="<?php echo esc_attr($coupons); ?>" class="regular-text" placeholder="e.g., SAVE20-YourSite" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="afflink" value="" class="regular-text" placeholder="https://affiliate-link.com/?ref=yourid" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupon'); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[ccap_coupon]</code></p>
            <h2>Pro Features</h2>
            <p>Upgrade for unlimited coupons, analytics, auto-expiry, and API integrations. <a href="#" onclick="alert('Pro version coming soon!')">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $coupons = get_option('ccap_coupons', 'SAVE10-YourSite');
        $afflink = get_option('ccap_afflink', '');
        $clicks = get_option('ccap_clicks', 0) + 1;
        update_option('ccap_clicks', $clicks);

        ob_start();
        ?>
        <div style="border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9;">
            <h3>Exclusive Coupon! 🔥</h3>
            <div style="font-size: 2em; color: #0073aa; margin: 10px 0;"><?php echo esc_html($coupons); ?></div>
            <p>Save 20% on your purchase! Limited time only.</p>
            <?php if ($afflink): ?>
            <a href="<?php echo esc_url($afflink . (strpos($afflink, '?') === false ? '?coupon=' : '&coupon=') . $coupons); ?>" target="_blank" class="button button-primary button-large" style="padding: 12px 24px;">Redeem Now</a>
            <?php endif; ?>
            <p style="font-size: 0.8em; margin-top: 15px;">Used <?php echo $clicks; ?> times today</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ccap_coupons')) {
            update_option('ccap_coupons', 'SAVE10-YourSite');
        }
    }
}

CustomCouponAffiliatePro::get_instance();
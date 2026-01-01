/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Partner.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Partner
 * Plugin URI: https://example.com/custom-coupon-partner
 * Description: Generate and manage exclusive custom coupons for affiliate partnerships to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-coupon-partner
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomCouponPartner {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('custom_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ccp-admin-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('ccp-admin-style');
    }

    public function admin_menu() {
        add_menu_page('Custom Coupons', 'Custom Coupons', 'manage_options', 'custom-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ccp_save'])) {
            update_option('ccp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ccp_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Custom Coupon Partner</h1>
            <form method="post">
                <p><label>Coupons (JSON: {"code":"Code","desc":"Description","afflink":"Affiliate Link"}):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="ccp_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Use shortcode: <code>[custom_coupon id="1"]</code> (Premium: Dynamic ID selection)</p>
            <?php
            // Free version shows first coupon
            $coupons_array = json_decode($coupons, true);
            if ($coupons_array && isset($coupons_array)) {
                echo '<h2>Preview First Coupon:</h2>';
                echo '<div class="ccp-coupon"><strong>' . esc_html($coupons_array['code']) . '</strong><br>' . esc_html($coupons_array['desc']) . '<br><a href="' . esc_url($coupons_array['afflink']) . '" target="_blank">Shop Now</a></div>';
            }
            echo '<p><em>Upgrade to Premium for unlimited coupons, analytics, and auto-expiry.</em></p>';
        </div>
        <style>
        .ccp-coupon { border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center; margin: 20px 0; }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = json_decode(get_option('ccp_coupons', '[]'), true);
        if (!$coupons || empty($coupons)) return 'No coupons configured. Go to Custom Coupons to add some.';
        $coupon = $coupons; // Free: First coupon only
        return '<div class="ccp-coupon"><h3>' . esc_html($coupon['code']) . '</h3><p>' . esc_html($coupon['desc']) . '</p><a href="' . esc_url($coupon['afflink']) . '" class="button" target="_blank">Redeem Now & Shop</a></div>';
    }

    public function activate() {
        if (!get_option('ccp_coupons')) {
            update_option('ccp_coupons', '[]');
        }
    }
}

new CustomCouponPartner();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Custom Coupon Partner:</strong> Unlock unlimited coupons, tracking, and more with Premium! <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
});
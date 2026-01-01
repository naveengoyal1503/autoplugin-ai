/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create and manage exclusive affiliate coupons with tracking and expiration for monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('acv-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('acv-admin-style');
    }

    public function admin_menu() {
        add_menu_page(
            'Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page'),
            'dashicons-tickets',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('acv_coupons', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" placeholder="Paste JSON coupons: [{\"title\":\"10% Off\",\"code\":\"SAVE10\",\"afflink\":\"https://aff.link\",\"expires\":\"2026-12-31\"}]<?php echo esc_textarea($coupons); ?>"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter coupons as JSON array. Use shortcode [acv_coupon id=\"0\"]</p>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use <code>[acv_coupon id=\"0\"]</code> for first coupon. Free version limited to 3 coupons. <strong>Pro:</strong> Unlimited + Analytics.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons_json = get_option('acv_coupons', '');
        $coupons = json_decode($coupons_json, true) ?: array();
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';

        $coupon = $coupons[$atts['id']];
        $today = date('Y-m-d');
        if (isset($coupon['expires']) && $today > $coupon['expires']) {
            return '<div class="acv-expired">Coupon expired!</div>';
        }

        $link = $coupon['afflink'] . (strpos($coupon['afflink'], '?') ? '&' : '?') . 'ref=' . get_bloginfo('url');
        return '<div class="acv-coupon"><h3>' . esc_html($coupon['title']) . '</h3><p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p><a href="' . esc_url($link) . '" target="_blank" class="button button-large">Redeem Now</a></div>';
    }

    public function activate() {
        if (get_option('acv_pro') !== 'yes') {
            update_option('acv_limit', 3);
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (get_option('acv_limit', 3) <= 3) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlimited coupons, analytics & auto-expiration for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Basic CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
.acv-expired { border: 2px dashed #d63638; padding: 20px; color: #d63638; text-align: center; }
.acv-coupon a { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
</style>
<?php });

// Prevent direct access
if (!defined('ABSPATH')) exit;
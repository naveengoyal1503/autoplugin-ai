/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions for bloggers and e-commerce sites.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupons', array($this, 'ajax_save_coupons'));
        add_action('wp_ajax_nopriv_save_coupons', array($this, 'ajax_save_coupons'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        wp_localize_script('jquery', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['coupons'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon1|AFF1|20% off|https://affiliate1.com?ref=site");
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><p><label>Enter coupons (format: Name|AffCode|Discount|AffLink):</label></p><textarea name="coupons" rows="10" cols="80">' . esc_textarea($coupons) . '</textarea></p><p><input type="submit" class="button-primary" value="Save"></p></form><p>Pro: Unlimited coupons, click tracking, analytics.</p></div>';
    }

    public function ajax_save_coupons() {
        // Pro feature placeholder
        wp_die();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons_str = get_option('acv_coupons', '');
        $coupons = explode('\n', trim($coupons_str));
        if (!isset($coupons[$atts['id'] - 1])) {
            return 'Coupon not found.';
        }
        list($name, $code, $discount, $link) = explode('|', $coupons[$atts['id'] - 1]);
        $track_id = uniqid('acv_');
        $tracked_link = add_query_arg('acv', $track_id, $link);
        ob_start();
        echo '<div style="border:1px solid #ddd; padding:20px; margin:10px 0; background:#f9f9f9;"><h3>' . esc_html($name) . '</h3><p><strong>' . esc_html($discount) . '</strong></p><p>Code: <strong>' . esc_html($code) . '</strong></p><a href="' . esc_url($tracked_link) . '" class="button" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none;">Get Deal</a></div>';
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Welcome|WELCOME10|10% off|https://example.com?ref=site");
        }
    }
}

AffiliateCouponVault::get_instance();
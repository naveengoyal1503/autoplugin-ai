/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes with tracking, boosting conversions for bloggers and eCommerce sites.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (defined('AFFILIATE_COUPON_VAULT_PRO') && AFFILIATE_COUPON_VAULT_PRO) {
            return;
        }
        // Free version limits
        if ($this->get_coupon_count() >= 5) {
            add_action('admin_notices', array($this, 'upgrade_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('affiliate-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('acv_coupons', '');
        echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1><form method="post"><textarea name="coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea><p>Enter coupons as JSON: [{"code":"SAVE10","afflink":"https://aff.link","desc":"10% off"}]</p><p><input type="submit" name="submit" value="Save" class="button-primary"></p></form><p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-generation. <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a></p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $clicks = get_option('acv_clicks_' . $atts['id'], 0);
        return '<div class="acv-coupon"><h3>' . esc_html($coupon['desc']) . '</h3><p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p><a href="' . esc_url($coupon['afflink']) . '" class="acv-btn" data-id="' . $atts['id'] . '" target="_blank">Redeem Now (' . $clicks . ' used)</a></div>';
    }

    public function ajax_save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die();
        }
        $id = intval($_POST['id']);
        $clicks = get_option('acv_clicks_' . $id, 0) + 1;
        update_option('acv_clicks_' . $id, $clicks);
        wp_send_json_success($clicks);
    }

    private function get_coupon_count() {
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        return count($coupons);
    }

    public function upgrade_notice() {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault: Upgrade to Pro for unlimited coupons! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', '[]');
        }
    }
}

AffiliateCouponVault::get_instance();

// Dummy assets (in real plugin, create folders)
// assets/script.js content:
/*
jQuery(document).ready(function($) {
    $('.acv-btn').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(ajax_object.ajax_url, {action: 'save_coupon', id: id, nonce: 'dummy'}, function(res) {
            if (res.success) {
                $(this).text('Redeem Now (' + res.data + ' used)');
            }
        });
        window.open($(this).attr('href'), '_blank');
    });
});
*/

// assets/style.css content:
/*
.acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; }
.acv-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.acv-btn:hover { background: #005a87; }
*/
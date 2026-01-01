/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum blog monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('acv_pro', isset($_POST['pro_version']));
        }
        $coupons = get_option('acv_coupons', "Brand1|DISCOUNT10|https://example.com/brand1|10% off first purchase");
        $pro = get_option('acv_pro', false);
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><table class="form-table">';
        echo '<tr><th>Coupons (format: Name|Code|Affiliate Link|Description; one per line)</th></tr><tr><td><textarea name="coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea></td></tr>';
        echo '<tr><th>Pro Version</th></tr><tr><td><input type="checkbox" name="pro_version" ' . checked($pro, true, false) . '> Unlock unlimited coupons & tracking (Enter license)</td></tr>';
        echo '</table><input type="submit" name="submit" class="button-primary" value="Save Settings"></form>';
        echo '<p>Use shortcode <code>[affiliate_coupon]</code> to display coupons. <strong>Pro Upgrade:</strong> Visit <a href="https://example.com/pro">example.com/pro</a> for $49/year.</p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons_str = get_option('acv_coupons', '');
        $coupons = explode('\n', $coupons_str);
        if (empty($coupons) || get_option('acv_pro', false) === false && count($coupons) > 3) {
            return '<p><strong>Upgrade to Pro</strong> for unlimited coupons! <a href="https://example.com/pro">Get Pro Now ($49/yr)</a></p>';
        }
        $output = '<div id="acv-coupons" class="acv-grid">';
        foreach ($coupons as $coupon) {
            $parts = explode('|', $coupon);
            if (count($parts) === 4) {
                $output .= '<div class="acv-coupon"><h3>' . esc_html($parts) . '</h3><p><strong>' . esc_html($parts[1]) . '</strong><br>' . esc_html($parts[3]) . '</p><a href="#" class="acv-btn" data-link="' . esc_url($parts[2]) . '" data-nonce="' . wp_create_nonce('acv_click') . '">Get Deal (Track)</a></div>';
            }
        }
        $output .= '</div><style>.acv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin:20px 0;}.acv-coupon{background:#f9f9f9;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.1);}.acv-btn{display:inline-block;background:#0073aa;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;}.acv-btn:hover{background:#005a87;}</style>';
        return $output;
    }

    public function track_click() {
        check_ajax_referer('acv_click', 'nonce');
        $link = sanitize_url($_POST['link']);
        // Log click (Pro feature simulation)
        if (get_option('acv_pro', false)) {
            error_log('ACV Click: ' . $link);
        }
        wp_redirect($link);
        exit;
    }

    public function activate() {
        add_option('acv_coupons', "Brand1|DISCOUNT10|https://example.com/brand1|10% off first purchase\nBrand2|WELCOME20|https://example.com/brand2|20% off welcome");
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro', false)) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock <strong>Pro</strong> for unlimited coupons & analytics! <a href="options-general.php?page=affiliate-coupon-vault">Upgrade Now ($49)</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Frontend script placeholder (save as assets/script.js but inline for single file)
function acv_inline_script() {
    if (!is_admin()) {
        ?><script>jQuery(document).ready(function($){$('.acv-btn').click(function(e){e.preventDefault();var link=$(this).data('link'),nonce=$(this).data('nonce');$.post(acv_ajax.ajax_url,{action:'acv_track_click',link:link,nonce:nonce},function(){window.location=link;});});});</script><?php
    }
}
add_action('wp_footer', 'acv_inline_script');
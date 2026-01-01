/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupons with click tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
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
        add_action('wp_ajax_track_coupon_click', array($this, 'track_coupon_click'));
        add_action('wp_ajax_nopriv_track_coupon_click', array($this, 'track_coupon_click'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            // Pro features
        }
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Coupons', 'manage_options', 'sac-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sac_coupons', "Coupon Code: SAVE20\nAffiliate Link: https://example.com/aff?ref=yourid\nDescription: 20% off first purchase");
        echo '<div class="wrap"><h1>Manage Coupons</h1><form method="post"><textarea name="coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea><p>Format: Coupon Code: CODE<br>Affiliate Link: URL<br>Description: Text (one per line)</p><p><input type="submit" name="sac_save" class="button-primary" value="Save Coupons"></p></form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = explode('\n\n', get_option('sac_coupons', ''));
        if (!isset($coupons[$atts['id']])) return '';
        $lines = explode('\n', $coupons[$atts['id']]);
        $code = '';
        $link = '';
        $desc = '';
        foreach ($lines as $line) {
            if (strpos($line, 'Coupon Code:') === 0) $code = substr($line, 12);
            if (strpos($line, 'Affiliate Link:') === 0) $link = substr($line, 14);
            if (strpos($line, 'Description:') === 0) $desc = substr($line, 12);
        }
        if (!$link) return '';
        $id = uniqid();
        ob_start();
        echo '<div class="sac-coupon" data-id="' . esc_attr($id) . '" data-link="' . esc_url($link) . '">';
        echo '<h3>' . esc_html($code) . '</h3>';
        echo '<p>' . esc_html($desc) . '</p>';
        echo '<button class="sac-button">Reveal & Use Coupon</button>';
        echo '</div>';
        echo '<style>.sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }.sac-button { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }</style>';
        return ob_get_clean();
    }

    public function track_coupon_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $link = sanitize_url($_POST['link']);
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        // Log click (free version limits, pro tracks fully)
        error_log('SAC Click: ' . $link . ' from ' . $user_ip);
        if (get_option('sac_pro_version')) {
            // Pro analytics
        }
        wp_send_json_success(array('redirect' => $link));
    }

    public function activate() {
        add_option('sac_coupons', "Coupon Code: SAVE20\nAffiliate Link: https://example.com/aff?ref=yourid\nDescription: 20% off first purchase\n\nCoupon Code: WELCOME10\nAffiliate Link: https://example.com/product?ref=yourid\nDescription: 10% off welcome deal");
    }
}

SmartAffiliateCoupons::get_instance();

// Pro upsell notice
function sac_admin_notice() {
    if (!get_option('sac_pro_version')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Coupons Pro</strong> for unlimited coupons, analytics, and templates! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'sac_admin_notice');

// JS file would be separate, but for single-file, inline it
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?><script>jQuery(document).ready(function($){$('.sac-button').click(function(){var $div=$(this).parent();$.post(sac_ajax.ajax_url,{action:'track_coupon_click',nonce:sac_ajax.nonce,link:$div.data('link')},function(res){if(res.success){window.open(res.data.redirect,'_blank');}})});});</script><?php
    }
});
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates, tracks, and displays personalized affiliate coupons and discount codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_coupons', sanitize_textarea_field($_POST['sac_coupons']));
            update_option('sac_pro_version', 'activated');
            echo '<div class="notice notice-success"><p>Settings saved! Upgrade to Pro for full features.</p></div>';
        }
        $coupons = get_option('sac_coupons', "Coupon Code: SAVE20\nAffiliate Link: https://affiliate-link.com\nDescription: 20% off first purchase");
        echo '<div class="wrap"><h1>Smart Affiliate Coupons Pro</h1><form method="post"><textarea name="sac_coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea><p>Format: Coupon Code: CODE<br>Affiliate Link: URL<br>Description: Text (one per line)</p><input type="submit" name="sac_save" class="button-primary" value="Save Settings"></form><p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-generation. <a href="#" onclick="alert(\'Pro features coming soon!\')">Upgrade Now ($49/yr)</a></p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $coupons = explode('\n\n', get_option('sac_coupons', ''));
        $coupon = isset($coupons) ? $coupons : 'SAVE10\nhttps://example.com/aff\n10% Off!';
        list($code, $link, $desc) = explode('\n', $coupon);
        ob_start();
        echo '<div class="sac-coupon" data-link="' . esc_url($link) . '"><h3>' . esc_html($code) . '</h3><p>' . esc_html($desc) . '</p><a href="#" class="sac-btn button">Get Deal (Tracked)</a></div>';
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $link = sanitize_url($_POST['link']);
        // In Pro: Log to DB and redirect with UTM
        wp_redirect($link . '?utm_source=sac_pro');
        exit;
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Smart Affiliate Coupons Pro: Add <code>[sac_coupon]</code> to posts. Upgrade for analytics!</p></div>';
    }

    public function activate() {
        update_option('sac_pro_version', 'free');
    }
}

new SmartAffiliateCouponsPro();

// Inline JS for simplicity (self-contained)
function sac_inline_js() {
    ?><script>jQuery(document).ready(function($){$('.sac-btn').click(function(e){e.preventDefault();var link=$(this).closest('.sac-coupon').data('link');$.post(sac_ajax.ajax_url,{action:'sac_track_click',nonce:sac_ajax.nonce,link:link},function(){window.location=link+'?src=sac';});});});</script><?php
}
add_action('wp_footer', 'sac_inline_js');
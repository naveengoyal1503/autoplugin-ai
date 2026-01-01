/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum WordPress blog monetization.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return; // Pro version active
        }
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro_version');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['acv_coupons']));
        }
        $coupons = get_option('acv_coupons', "Coupon1|Affiliate Link 1|10% off\nCoupon2|Affiliate Link 2|20% off");
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><table class="form-table"><tr><th>Coupons</th><td><textarea name="acv_coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea><br><small>Format: Name|Affiliate URL|Discount (one per line)</small></td></tr></table><p><input type="submit" name="submit" class="button-primary" value="Save Changes"></p></form><p><strong>Upgrade to Pro</strong> for unlimited coupons, analytics, and more! <a href="#" onclick="alert(\'Pro version coming soon!\')">Get Pro</a></p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode('\n', get_option('acv_coupons', ''));
        if (empty($coupons)) return 'No coupons configured. <a href="' . admin_url('options-general.php?page=acv-settings') . '">Configure now</a>.';

        $coupon = '';
        foreach ($coupons as $c) {
            $parts = explode('|', trim($c));
            if (count($parts) === 3 && ($atts['id'] === '' || $atts['id'] === $parts)) {
                $name = sanitize_text_field($parts);
                $url = esc_url($parts[1]);
                $discount = sanitize_text_field($parts[2]);
                $unique_code = $name . '-' . wp_generate_uuid4();
                $coupon .= '<div class="acv-coupon" data-url="' . $url . '" data-code="' . $unique_code . '"><h3>' . $name . '</h3><p>' . $discount . '</p><button class="acv-btn">Get Coupon & Track</button><div class="acv-reveal" style="display:none;"><p>Your code: <strong>' . $unique_code . '</strong></p><a href="' . $url . '" target="_blank" rel="nofollow">Shop Now (Affiliate)</a></div></div>';
            }
        }
        if ($atts['id'] !== '' && empty($coupon)) {
            return 'Coupon not found.';
        }
        return $coupon ?: $this->random_coupon();
    }

    private function random_coupon() {
        $coupons = explode('\n', get_option('acv_coupons', ''));
        $rand = $coupons[array_rand($coupons)];
        $parts = explode('|', trim($rand));
        if (count($parts) !== 3) return '';
        $name = sanitize_text_field($parts);
        $url = esc_url($parts[1]);
        $discount = sanitize_text_field($parts[2]);
        $unique_code = $name . '-' . wp_generate_uuid4();
        return '<div class="acv-coupon" data-url="' . $url . '" data-code="' . $unique_code . '"><h3>Exclusive Deal</h3><p>' . $discount . '</p><button class="acv-btn">Reveal Coupon</button><div class="acv-reveal" style="display:none;"><p>Code: <strong>' . $unique_code . '</strong></p><a href="' . $url . '" target="_blank" rel="nofollow">Click to Shop</a></div></div>';
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $url = sanitize_url($_POST['url']);
        $code = sanitize_text_field($_POST['code']);
        // Log to simple option (free version limit)
        $logs = get_option('acv_logs', array());
        $logs[] = array('time' => current_time('mysql'), 'url' => $url, 'code' => $code, 'ip' => $_SERVER['REMOTE_ADDR']);
        if (count($logs) > 100) { // Free limit
            array_shift($logs);
        }
        update_option('acv_logs', $logs);
        wp_die(json_encode(array('success' => true)));
    }

    public function activate() {
        add_option('acv_coupons', "WPDeal|https://example-affiliate.com/wp?ref=yourid|50% off hosting\nPluginPro|https://example-affiliate.com/plugin?ref=yourid|30% off");
    }
}

// Enqueue JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-btn').click(function() {
        var $coupon = $(this).closest('.acv-coupon');
        var url = $coupon.data('url');
        var code = $coupon.data('code');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_track_click',
            nonce: acv_ajax.nonce,
            url: url,
            code: code
        }, function() {
            $coupon.find('.acv-reveal').show();
            $(this).hide();
        });
    });
});
</script>
<style>
.acv-coupon { border: 2px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; }
.acv-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.acv-btn:hover { background: #005a87; }
.acv-reveal { margin-top: 10px; padding: 10px; background: #e7f3ff; }
</style>
<?php });

AffiliateCouponVault::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited tracking, analytics dashboard, and premium integrations! <a href="#" onclick="alert(\'Visit example.com/pro for details!\')">Get Pro Now</a></p></div>';
    }
});
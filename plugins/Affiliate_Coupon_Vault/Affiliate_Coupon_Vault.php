/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost conversions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro_key');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('acv_pro_key', sanitize_text_field($_POST['pro_key']));
        }
        $coupons = get_option('acv_coupons', 'Coupon Code: SAVE20\nAffiliate Link: https://example.com/affiliate\nDescription: 20% off first purchase');
        $pro_key = get_option('acv_pro_key', '');
        $is_pro = !empty($pro_key) && $pro_key === 'pro-activated';
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post">';
        echo '<textarea name="coupons" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea><p>Format: Coupon Code: CODE<br>Affiliate Link: URL<br>Description: Text (one per line)</p>';
        echo '<p><label>Pro Key: <input type="text" name="pro_key" value="' . esc_attr($pro_key) . '"></label> ';
        if ($is_pro) echo '<strong>PRO ACTIVATED!</strong>'; else echo '(Enter "pro-activated" for pro features)';
        echo '</p><p><input type="submit" name="submit" class="button-primary" value="Save Settings"></p></form>';
        echo '<p>Use shortcode: <code>[affiliate_coupon id="1"]</code> (id starts from 1)</p>';
        echo '</div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        $coupons = explode('\n\n', get_option('acv_coupons', ''));
        $id = intval($atts['id']) - 1;
        if (!isset($coupons[$id])) return 'Coupon not found.';
        $coupon_data = array();
        foreach (explode('\n', $coupons[$id]) as $line) {
            if (strpos($line, 'Coupon Code:') === 0) $coupon_data['code'] = substr($line, 12);
            if (strpos($line, 'Affiliate Link:') === 0) $coupon_data['link'] = substr($line, 15);
            if (strpos($line, 'Description:') === 0) $coupon_data['desc'] = substr($line, 12);
        }
        if (empty($coupon_data['link'])) return 'Invalid coupon.';

        $click_id = uniqid();
        $track_url = add_query_arg('acv_click', $click_id, $coupon_data['link']);

        ob_start();
        echo '<div class="acv-coupon" data-click-id="' . esc_attr($click_id) . '">';
        echo '<h3>' . esc_html($coupon_data['desc']) . '</h3>';
        echo '<div class="acv-code">' . esc_html($coupon_data['code']) . '</div>';
        echo '<a href="' . esc_url($track_url) . '" class="acv-button" target="_blank">Get Deal Now (Affiliate)</a>';
        echo '</div>';
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', 'Coupon Code: SAVE20\nAffiliate Link: https://example.com/affiliate-link-1\nDescription: 20% off on all products\n\nCoupon Code: WELCOME10\nAffiliate Link: https://example.com/affiliate-link-2\nDescription: 10% off first order');
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro check
function acv_is_pro() {
    return get_option('acv_pro_key', '') === 'pro-activated';
}

/* Embedded minimal JS and CSS */
function acv_inline_scripts() {
    if (acv_is_pro()) {
        ?><script>console.log('Pro features active: Advanced tracking enabled.');</script><?php
    }
}
add_action('wp_footer', 'acv_inline_scripts');

?>
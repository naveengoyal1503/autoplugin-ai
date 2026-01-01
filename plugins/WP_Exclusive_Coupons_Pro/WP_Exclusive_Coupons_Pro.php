/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPExclusiveCoupons {
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
        add_action('wp_ajax_wpec_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('wpec_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wpec-frontend', 'wpec_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('wpec_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'wpec-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" style="width:100%;" placeholder='[{"name":"10% Off","affiliate":"https://affiliate.com/?ref=blog","code":"BLOG10","expires":"2026-12-31"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON array: {"name":"Coupon Name","affiliate":"Affiliate URL","code":"Promo Code","expires":"YYYY-MM-DD"}</p>
                <p><input type="submit" name="wpec_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[wpec_coupons]</code> to display coupons on any page/post.</p>
            <h2>Pro Features</h2>
            <p>Upgrade to Pro for unlimited coupons, analytics, auto-expiration, and more! <a href="#pro">Buy Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('wpec_nonce', 'nonce');
        $code = wp_generate_uuid4();
        wp_send_json_success(array('code' => $code));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_json = get_option('wpec_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (empty($coupons) || !is_array($coupons)) {
            return '<p>No coupons available.</p>';
        }
        $output = '<div class="wpec-coupons">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= intval($atts['limit'])) break;
            if (isset($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp')) continue;
            $output .= '<div class="wpec-coupon">';
            $output .= '<h3>' . esc_html($coupon['name']) . '</h3>';
            $output .= '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            $output .= '<a href="' . esc_url($coupon['affiliate']) . '" target="_blank" class="button">Get Deal</a>';
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        $output .= '<style>.wpec-coupon {border:1px solid #ddd; padding:15px; margin:10px 0; background:#f9f9f9;}.wpec-coupon h3 {margin-top:0;}</style>';
        return $output;
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', '[]');
        }
    }
}

WPExclusiveCoupons::get_instance();

// Pro Upsell Notice
function wpec_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>WP Exclusive Coupons Pro</strong>: Unlimited coupons, analytics & integrations. <a href="https://example.com/pro">Upgrade Now ($49)</a></p></div>';
}
add_action('admin_notices', 'wpec_pro_notice');

// Frontend JS (inline for single file)
function wpec_inline_js() {
    ?>
    <script>jQuery(document).ready(function($) { $('.wpec-generate').click(function() { $.post(wpec_ajax.ajax_url, {action:'wpec_generate_coupon', nonce:wpec_ajax.nonce}, function(res) { if(res.success) alert('New code: ' + res.data.code); }); }); });</script>
    <?php
}
add_action('wp_footer', 'wpec_inline_js');
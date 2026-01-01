/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and displays personalized deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_track_click', array($this, 'ajax_track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('jquery');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (JSON format: {"code":"CODE","afflink":"URL","desc":"Description"}):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode: [affiliate_coupons]</h2>
            <p>Pro: Upgrade for analytics and unlimited coupons.</p>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (current_user_can('manage_options')) {
            update_option('acv_coupons', sanitize_text_field($_POST['data']));
            wp_send_json_success('Saved');
        }
        wp_send_json_error();
    }

    public function ajax_track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        error_log('ACV Click: ' . $coupon_id . ' from ' . $_SERVER['REMOTE_ADDR']);
        wp_send_json_success();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', json_encode(array(
                array('code' => 'SAVE10', 'afflink' => 'https://example.com/aff1', 'desc' => '10% off at Example Store')
            )));
        }
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Shortcode to display coupons
function acv_shortcode($atts) {
    $coupons_json = get_option('acv_coupons', '[]');
    $coupons = json_decode($coupons_json, true);
    if (empty($coupons) || !is_array($coupons)) {
        return '<p>No coupons available.</p>';
    }

    ob_start();
    echo '<div id="acv-coupons" style="max-width:400px;">';
    foreach ($coupons as $i => $coupon) {
        $id = 'coupon-' . $i;
        echo '<div class="acv-coupon" style="border:1px solid #ddd; margin:10px 0; padding:15px; background:#f9f9f9;">
                <h4>' . esc_html($coupon['desc']) . '</h4>
                <p><strong>Code:</strong> <code>' . esc_html($coupon['code']) . '</code></p>
                <a href="#" class="acv-btn" data-id="' . esc_attr($i) . '" data-link="' . esc_url($coupon['afflink']) . '" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Get Deal (Affiliate)</a>
              </div>';
    }
    echo '</div>';
    echo '<script>document.addEventListener("DOMContentLoaded", function() { jQuery(".acv-btn").click(function(e) { e.preventDefault(); var id = jQuery(this).data("id"); var link = jQuery(this).data("link"); jQuery.post(acv_ajax.ajax_url, {action: "acv_track_click", coupon_id: id, nonce: acv_ajax.nonce}, function() { window.open(link, "_blank"); }); }); }); </script>';
    return ob_get_clean();
}
add_shortcode('affiliate_coupons', 'acv_shortcode');

AffiliateCouponVault::get_instance();

// Pro upsell notice (simplified)
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Affiliate Coupon Vault: <strong>Upgrade to Pro</strong> for unlimited coupons, analytics dashboard, and A/B testing! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
});
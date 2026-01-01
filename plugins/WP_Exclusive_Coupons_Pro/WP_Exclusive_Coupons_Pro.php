/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and displays exclusive, trackable coupon codes for affiliate products, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons {
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
        add_options_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'wpec-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('wpec_api_key', sanitize_text_field($_POST['api_key']));
            update_option('wpec_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Coupons settings saved!</p></div>';
        }
        $api_key = get_option('wpec_api_key', '');
        $aff_id = get_option('wpec_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($aff_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and custom domains for $49/year!</p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('wpec_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $product = sanitize_text_field($_POST['product']);
        $code = 'WPEC-' . wp_generate_uuid4() . substr(md5($product), 0, 8);
        $link = add_query_arg(array('coupon' => $code, 'aff' => get_option('wpec_affiliate_id', '')), $product);
        wp_send_json_success(array('code' => $code, 'link' => $link));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts, 'wpec_coupons');
        ob_start();
        echo '<div id="wpec-coupons" class="wpec-grid">';
        for ($i = 0; $i < intval($atts['count']); $i++) {
            echo '<div class="wpec-coupon-item">';
            echo '<button class="wpec-generate-btn" data-product="https://example-affiliate.com/product' . $i . '">Get Exclusive Coupon</button>';
            echo '<div class="wpec-coupon-code"></div>';
            echo '<a class="wpec-coupon-link" href="#" target="_blank">Use Coupon</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function activate() {
        add_option('wpec_version', '1.0.0');
    }
}

WP_Exclusive_Coupons::get_instance();

// Pro upsell notice
function wpec_pro_notice() {
    if (!get_option('wpec_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Exclusive Coupons Pro</strong> for unlimited features! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'wpec_pro_notice');

// Dummy assets (in real plugin, create assets folder)
function wpec_asset_exists($path) { return true; } // Placeholder
?>
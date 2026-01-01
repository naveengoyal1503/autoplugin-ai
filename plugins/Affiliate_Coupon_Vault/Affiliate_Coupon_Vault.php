/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost your commissions.
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
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('acv_coupon_display', array($this, 'coupon_display_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv-settings');
        add_settings_field('acv_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'acv-settings', 'acv_main');
        add_settings_field('acv_affiliate_id', 'Your Affiliate ID', array($this, 'affiliate_id_field'), 'acv-settings', 'acv_main');
    }

    public function api_key_field() {
        $options = get_option('acv_options');
        echo '<input type="text" name="acv_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
    }

    public function affiliate_id_field() {
        $options = get_option('acv_options');
        echo '<input type="text" name="acv_options[affiliate_id]" value="' . esc_attr($options['affiliate_id'] ?? '') . '" class="regular-text" />';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, advanced analytics, and custom templates for $49/year!</p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $options = get_option('acv_options');
        $product = sanitize_text_field($_POST['product'] ?? '');
        if (empty($options['api_key']) || empty($product)) {
            wp_die('Missing settings');
        }
        // Simulate coupon generation (replace with real API)
        $coupon_code = 'SAVE' . wp_rand(1000, 9999);
        $aff_link = 'https://affiliate.example.com/?product=' . urlencode($product) . '&aff=' . $options['affiliate_id'] . '&coupon=' . $coupon_code;
        $coupon = array(
            'code' => $coupon_code,
            'discount' => '20% OFF',
            'link' => $aff_link,
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'product' => $product
        );
        wp_send_json_success($coupon);
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('product' => ''), $atts);
        ob_start();
        ?>
        <div id="acv-coupon-<?php echo esc_attr($atts['product']); ?>" class="acv-coupon-widget">
            <p>Generate exclusive coupon for <strong><?php echo esc_html($atts['product']); ?></strong></p>
            <button id="acv-gen-<?php echo esc_attr($atts['product']); ?>" class="button acv-generate">Get Coupon</button>
            <div id="acv-result-<?php echo esc_attr($atts['product']); ?>" style="display:none;">
                <h4>Your Exclusive Coupon:</h4>
                <div class="acv-coupon-code"></div>
                <a href="#" class="acv-aff-link button" target="_blank">Claim Discount</a>
            </div>
        </div>
        <script>
        jQuery(function($) {
            $('#acv-gen-<?php echo esc_attr($atts['product']); ?>').click(function() {
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    nonce: acv_ajax.nonce,
                    product: '<?php echo esc_js($atts['product']); ?>'
                }, function(resp) {
                    if (resp.success) {
                        $('#acv-result-<?php echo esc_attr($atts['product']); ?> .acv-coupon-code').html('<strong>' + resp.data.code + '</strong> (' + resp.data.discount + ')<br>Expires: ' + resp.data.expires);
                        $('#acv-result-<?php echo esc_attr($atts['product']); ?> .acv-aff-link').attr('href', resp.data.link);
                        $('#acv-result-<?php echo esc_attr($atts['product']); ?>').show();
                        $(this).hide();
                    }
                });
            });
        });
        </script>
        <style>
        .acv-coupon-widget { border: 1px solid #ddd; padding: 20px; margin: 10px 0; background: #f9f9f9; }
        .acv-coupon-code { font-size: 24px; color: #e74c3c; margin: 10px 0; }
        .acv-aff-link { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('acv_options', array());
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for unlimited coupons & analytics! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');
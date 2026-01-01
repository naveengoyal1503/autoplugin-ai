/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with tracking to boost conversions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features here
        }
        register_setting('acv_settings', 'acv_api_keys');
        register_setting('acv_settings', 'acv_coupons');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'discount' => '10%',
            'code' => ''
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        $unique_code = $atts['code'] ?: uniqid('ACV-' . strtoupper($atts['affiliate']) . '-');

        $coupon = array(
            'affiliate' => sanitize_text_field($atts['affiliate']),
            'discount' => sanitize_text_field($atts['discount']),
            'code' => $unique_code,
            'link' => esc_url($this->build_affiliate_link($atts['affiliate'], $unique_code))
        );

        $coupons[$unique_code] = $coupon;
        update_option('acv_coupons', $coupons);

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3>Exclusive Deal! <?php echo esc_html($atts['discount']); ?> OFF</h3>
            <p><strong>Code: <span id="acv-code"><?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($coupon['link']); ?>" target="_blank" class="button button-large" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Shop Now & Save</a>
            <p style="font-size: 12px; margin-top: 10px;">Tracked via Affiliate Coupon Vault</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-copy-<?php echo esc_attr($unique_code); ?>').on('click', function() {
                navigator.clipboard.writeText('<?php echo esc_js($unique_code); ?>');
                $(this).text('Copied!');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function build_affiliate_link($affiliate, $code) {
        $api_keys = get_option('acv_api_keys', array());
        switch (strtolower($affiliate)) {
            case 'amazon':
                return 'https://amazon.com/?tag=' . $api_keys['amazon_tag'] . '&coupon=' . $code;
            case 'shopify':
                return 'https://yourshop.myshopify.com/discount/' . $code;
            default:
                return '#';
        }
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }

        $affiliate = sanitize_text_field($_POST['affiliate']);
        $discount = sanitize_text_field($_POST['discount']);
        $code = uniqid('ACV-' . strtoupper($affiliate) . '-');

        $response = array(
            'code' => $code,
            'link' => $this->build_affiliate_link($affiliate, $code),
            'discount' => $discount
        );

        wp_send_json_success($response);
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_settings_page');
    });

    function acv_settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="acv_api_keys[amazon_tag]" value="<?php echo esc_attr(get_option('acv_api_keys')['amazon_tag'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon affiliate="amazon" discount="20%"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard - <a href="#pro">Get Pro</a></p>
        </div>
        <?php
    }
}

AffiliateCouponVault::get_instance();

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like advanced tracking for $49/year! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
});
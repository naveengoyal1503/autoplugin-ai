/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon generator and manager that creates personalized discount codes, tracks affiliate commissions, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('scv_coupon_display', array($this, 'coupon_display_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('scv_api_key', '');
        add_option('scv_coupons', array());
    }

    public function admin_menu() {
        add_options_page(
            'Smart Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'smart-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['scv_api_key'])) {
            update_option('scv_api_key', sanitize_text_field($_POST['scv_api_key']));
        }
        $api_key = get_option('scv_api_key');
        $coupons = get_option('scv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (Premium)</th>
                        <td><input type="text" name="scv_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Your Coupons</h2>
            <button id="generate-coupon" class="button button-primary">Generate New Coupon</button>
            <div id="coupons-list">
                <?php foreach ($coupons as $coupon) : ?>
                    <div class="coupon-item">
                        <strong><?php echo esc_html($coupon['code']); ?></strong> - <?php echo esc_html($coupon['description']); ?>
                        <br><small>Affiliate: <?php echo esc_html($coupon['affiliate']); ?> | Uses: <?php echo intval($coupon['uses']); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-coupon').click(function() {
                $.post(ajaxurl, {
                    action: 'generate_coupon',
                    nonce: '<?php echo wp_create_nonce('scv_nonce'); ?>',
                    brand: $('#brand').val() || 'Generic',
                    discount: $('#discount').val() || '20'
                }, function(response) {
                    if (response.success) {
                        $('#coupons-list').append('<div class="coupon-item"><strong>' + response.data.code + '</strong> - ' + response.data.desc + '<br><small>Affiliate: ' + response.data.affiliate + ' | Uses: 0</small></div>');
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('scv_nonce', 'nonce');

        $brand = sanitize_text_field($_POST['brand'] ?? 'WordPress Deal');
        $discount = intval($_POST['discount'] ?? 20);

        // Simulate AI generation (premium would use real API like OpenAI)
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        $desc = "{$discount}% off on {$brand}!";
        $affiliate = "affiliate-link.com/?ref=" . strtolower($code);

        $coupons = get_option('scv_coupons', array());
        $coupons[] = array(
            'code' => $code,
            'description' => $desc,
            'affiliate' => $affiliate,
            'uses' => 0
        );
        update_option('scv_coupons', $coupons);

        wp_send_json_success(array(
            'code' => $code,
            'desc' => $desc,
            'affiliate' => $affiliate
        ));
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('scv_coupons', array());
        ob_start();
        if (!empty($coupons)) {
            $coupon = $coupons; // Display first or latest
            echo '<div class="scv-coupon" style="border:1px solid #ddd; padding:20px; background:#f9f9f9;">
                <h3>Exclusive Deal: ' . esc_html($coupon['description']) . '</h3>
                <div style="font-size:24px; color:#e74c3c;">' . esc_html($coupon['code']) . '</div>
                <p><a href="' . esc_url($coupon['affiliate']) . '" target="_blank" class="button">Get Deal Now</a></p>
                <small>Tracked uses: ' . intval($coupon['uses']) . '</small>
            </div>';
        }
        return ob_get_clean();
    }
}

SmartCouponVault::get_instance();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!get_option('scv_api_key')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI-powered coupon generation</strong> with <a href="https://example.com/premium">Smart Coupon Vault Premium</a> for $49/year! Track commissions and generate unlimited codes.</p></div>';
    }
});
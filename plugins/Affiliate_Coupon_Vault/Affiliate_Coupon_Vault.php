/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons to boost conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv');
        add_settings_field('acv_api_key', 'Affiliate API Key (Pro)', array($this, 'api_key_field'), 'acv', 'acv_main');
        add_settings_field('acv_default_discount', 'Default Discount %', array($this, 'default_discount_field'), 'acv', 'acv_main');
    }

    public function api_key_field() {
        $options = get_option('acv_options');
        echo '<input type="text" name="acv_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" placeholder="Enter Pro API Key"> <p>Upgrade to Pro for integrations.</p>';
    }

    public function default_discount_field() {
        $options = get_option('acv_options');
        echo '<input type="number" name="acv_options[default_discount]" value="' . esc_attr($options['default_discount'] ?? 10) . '" min="1" max="90">%';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and networks for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'product' => 'Featured Product',
            'url' => '',
        ), $atts);

        $options = get_option('acv_options');
        $discount = $options['default_discount'] ?? 10;
        $code = 'SAVE' . $discount . wp_generate_password(4, false);
        $coupon_url = $atts['url'] ?: home_url('/shop/');

        ob_start();
        ?>
        <div id="acv-coupon" class="acv-vault-coupon" data-product="<?php echo esc_attr($atts['product']); ?>" data-discount="<?php echo $discount; ?>">
            <h3>Exclusive Deal: <?php echo esc_html($atts['product']); ?></h3>
            <p>Use code: <strong><?php echo $code; ?></strong> for <strong><?php echo $discount; ?>% OFF</strong></p>
            <a href="<?php echo esc_url(add_query_arg('coupon', $code, $coupon_url)); ?>" class="acv-button" target="_blank">Get Deal Now (Affiliate Link)</a>
            <button class="acv-generate-new">New Coupon</button>
            <small>Generated exclusively for visitors</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $discount = intval($_POST['discount'] ?? 10);
        $code = 'SAVE' . $discount . wp_generate_password(4, false);
        wp_send_json_success(array('code' => $code));
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Pro Teaser Notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'settings_page_affiliate-coupon-vault') {
        echo '<div class="notice notice-info"><p><strong>Go Pro:</strong> Unlimited coupons, analytics, Amazon/ClickBank integration. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

// Frontend CSS
add_action('wp_head', function() { ?>
<style>
.acv-vault-coupon { background: linear-gradient(135deg, #ff6b6b, #feca57); padding: 20px; border-radius: 10px; text-align: center; max-width: 400px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.acv-vault-coupon h3 { color: white; margin: 0 0 10px; }
.acv-button { background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; margin: 10px 5px; }
.acv-button:hover { background: #218838; }
.acv-generate-new { background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
.acv-generate-new:hover { background: #5a6268; }
</style>
<?php });

// Frontend JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-generate-new').click(function() {
        var $coupon = $(this).closest('#acv-coupon');
        var discount = $coupon.data('discount');
        $.post(ajaxurl, {
            action: 'acv_generate_coupon',
            nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>',
            discount: discount
        }, function(response) {
            if (response.success) {
                $coupon.find('strong').text(response.data.code);
            }
        });
    });
});
</script>
<?php });
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features would go here
        }
        register_setting('acv_settings', 'acv_api_key');
        register_setting('acv_settings', 'acv_affiliate_id');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'default',
            'discount' => '10%',
            'affiliate' => get_option('acv_affiliate_id', ''),
        ), $atts);

        ob_start();
        ?>
        <div id="acv-coupon-<?php echo esc_attr($atts['product']); ?>" class="acv-coupon-container">
            <p>Grab <strong><?php echo esc_html($atts['discount']); ?></strong> off <?php echo esc_html($atts['product']); ?>!</p>
            <input type="text" id="acv-code-<?php echo esc_attr($atts['product']); ?>" readonly placeholder="Coupon code will appear here">
            <button id="acv-generate-<?php echo esc_attr($atts['product']); ?>">Generate Coupon</button>
            <a id="acv-link-<?php echo esc_attr($atts['product']); ?>" href="#" target="_blank" style="display:none;">Shop Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $product = sanitize_text_field($_POST['product']);
        $discount = sanitize_text_field($_POST['discount']);
        $affiliate = get_option('acv_affiliate_id');
        $code = 'ACV-' . substr(md5($product . time()), 0, 8);

        $tracking_url = add_query_arg(array(
            'coupon' => $code,
            'aff' => $affiliate,
        ), 'https://example.com/shop');

        wp_send_json_success(array(
            'code' => $code,
            'url' => $tracking_url,
        ));
    }
}

// Settings page
add_action('admin_menu', function() {
    add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_settings_page');
});

function acv_settings_page() {
    ?>
    <div class="wrap">
        <h1>Affiliate Coupon Vault Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('acv_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Affiliate ID</th>
                    <td><input type="text" name="acv_affiliate_id" value="<?php echo esc_attr(get_option('acv_affiliate_id')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Upgrade to Pro</strong> for unlimited coupons, analytics, and integrations. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
    </div>
    <?php
}

// Enqueue JS (inline for single file)
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-coupon-container button').on('click', function() {
                var container = $(this).closest('.acv-coupon-container');
                var product = container.attr('id').replace('acv-coupon-', '');
                var discount = container.find('strong').text();

                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    product: product,
                    discount: discount
                }, function(response) {
                    if (response.success) {
                        container.find('input').val(response.data.code);
                        container.find('a').attr('href', response.data.url).show();
                    }
                });
            });
        });
        </script>
        <?php
    }
});

AffiliateCouponVault::get_instance();

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics! <a href="https://example.com/pro">Upgrade Now ($49)</a></p></div>';
    }
});
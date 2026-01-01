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
 */

if (!defined('ABSPATH')) exit;

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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_api_key') === false) {
            add_option('acv_api_key', wp_generate_uuid4());
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => 'default',
            'discount' => '10%',
            'product' => 'Featured Product',
            'link' => '#',
        ), $atts);

        ob_start();
        ?>
        <div class="acv-coupon" data-affiliate-id="<?php echo esc_attr($atts['affiliate_id']); ?>" data-discount="<?php echo esc_attr($atts['discount']); ?>" data-product="<?php echo esc_attr($atts['product']); ?>" data-link="<?php echo esc_url($atts['link']); ?>">
            <div class="acv-coupon-code">Click to Generate Coupon!</div>
            <div class="acv-coupon-details">
                <strong><?php echo esc_html($atts['product']); ?></strong><br>
                Save <span class="acv-discount"><?php echo esc_html($atts['discount']); ?></span>!
            </div>
            <a href="#" class="acv-copy-btn">Copy Code</a>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; max-width: 400px; }
        .acv-coupon-code { font-size: 24px; font-weight: bold; color: #007cba; min-height: 40px; }
        .acv-coupon-details { margin: 10px 0; }
        .acv-copy-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .acv-copied { background: #46b450 !important; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $api_key = get_option('acv_api_key');
        $coupon_code = $affiliate_id . '-' . wp_generate_uuid4(4) . '-' . $api_key;
        $tracking_url = add_query_arg(array(
            'acv_coupon' => $coupon_code,
            'aff_id' => $affiliate_id,
            'ref' => $api_key
        ), $_POST['link']);

        wp_send_json_success(array(
            'code' => $coupon_code,
            'tracking_url' => $tracking_url,
            'clicks' => get_option('acv_clicks_' . $affiliate_id, 0) + 1
        ));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('acv_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics dashboard, and API integrations. <a href="https://example.com/pro" target="_blank">Upgrade now ($49/year)</a></p></div>';
    }
});

// JS file content would be enqueued separately, but for single-file:
function acv_inline_js() {
    if (wp_script_is('acv-script', 'enqueued')) {
        ?>
        <script>jQuery(document).ready(function($) {
            $('.acv-coupon').on('click', '.acv-coupon-code, .acv-copy-btn', function(e) {
                e.preventDefault();
                var $container = $(this).closest('.acv-coupon');
                var data = $container.data();
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    nonce: acv_ajax.nonce,
                    affiliate_id: data.affiliateId,
                    link: data.link
                }, function(response) {
                    if (response.success) {
                        $container.find('.acv-coupon-code').text(response.data.code).addClass('generated');
                        $container.find('.acv-copy-btn').attr('href', response.data.tracking_url).text('Go to Deal').addClass('acv-copied');
                    }
                });
            });
        });</script>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_js');
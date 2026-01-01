/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with click tracking for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCoupons {
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
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track_click', array($this, 'track_click'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_api_key') === false) {
            add_option('sac_api_key', wp_generate_uuid4());
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sac_nonce'),
            'api_key' => get_option('sac_api_key')
        ));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'code' => 'SAVE20',
            'description' => 'Get 20% off with this exclusive coupon!',
            'button_text' => 'Redeem Coupon'
        ), $atts);

        ob_start();
        ?>
        <div class="sac-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9;">
            <h3 style="color: #007cba;">🎉 Exclusive Coupon: <strong><?php echo esc_html($atts['code']); ?></strong></h3>
            <p><?php echo esc_html($atts['description']); ?></p>
            <button class="sac-button" data-url="<?php echo esc_url($atts['affiliate_url']); ?>" data-code="<?php echo esc_attr($atts['code']); ?>" style="background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;">
                <?php echo esc_html($atts['button_text']); ?> →
            </button>
            <p style="font-size: 12px; margin-top: 10px;">Tracked clicks: <span class="sac-clicks">0</span></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sac-button').click(function() {
                var url = $(this).data('url');
                var code = $(this).data('code');
                $.post(sac_ajax.ajax_url, {
                    action: 'sac_track_click',
                    nonce: sac_ajax.nonce,
                    url: url,
                    code: code,
                    api_key: sac_ajax.api_key
                }, function(response) {
                    if (response.success) {
                        window.open(url, '_blank');
                        $('.sac-clicks').text(response.data.clicks);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (sanitize_text_field($_POST['api_key']) !== get_option('sac_api_key')) {
            wp_die('Unauthorized');
        }

        $url = esc_url_raw($_POST['url']);
        $code = sanitize_text_field($_POST['code']);
        $key = 'sac_clicks_' . md5($url . $code);

        $clicks = (int) get_transient($key);
        $clicks++;
        set_transient($key, $clicks, WEEK_IN_SECONDS);

        wp_send_json_success(array('clicks' => $clicks));
    }

    public function activate() {
        add_option('sac_api_key', wp_generate_uuid4());
        flush_rewrite_rules();
    }
}

SmartAffiliateCoupons::get_instance();

// Premium upsell notice
function sac_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate Coupons:</strong> Unlock unlimited coupons, analytics dashboard, and custom designs with <a href="https://example.com/premium" target="_blank">Premium version</a> for $49/year!</p></div>';
}
add_action('admin_notices', 'sac_admin_notice');
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_api_key')) {
            // Premium features placeholder
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
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
        <div class="sac-coupon-container">
            <h3><?php echo esc_html($atts['product']); ?> - <?php echo esc_html($atts['discount']); ?> OFF</h3>
            <div class="sac-coupon-code" id="sac-code-<?php echo esc_attr($atts['affiliate_id']); ?>">GENERATING...</div>
            <button class="sac-generate-btn" data-affid="<?php echo esc_attr($atts['affiliate_id']); ?>">Generate My Coupon</button>
            <a href="<?php echo esc_url($atts['link']); ?}" class="sac-affiliate-link" target="_blank" data-track="<?php echo esc_attr($atts['affiliate_id']); ?>">Shop Now & Save</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('sac_nonce', 'nonce');
        $affid = sanitize_text_field($_POST['affid']);
        $code = 'SAC' . wp_generate_uuid4() . substr(md5($affid . time()), 0, 6);
        $tracking_url = add_query_arg(array('coupon' => $code, 'ref' => $affid), home_url('/'));

        wp_send_json_success(array(
            'code' => $code,
            'tracking' => $tracking_url
        ));
    }

    public function activate() {
        add_option('sac_version', '1.0.0');
        flush_rewrite_rules();
    }
}

SmartAffiliateCoupons::get_instance();

// Inline JS for simplicity (self-contained)
function sac_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sac-generate-btn').click(function() {
            var btn = $(this);
            var codeDiv = btn.siblings('.sac-coupon-code');
            var affid = btn.data('affid');
            btn.text('Generating...').prop('disabled', true);
            $.post(sac_ajax.ajax_url, {
                action: 'generate_coupon',
                nonce: sac_ajax.nonce,
                affid: affid
            }, function(resp) {
                if (resp.success) {
                    codeDiv.text(resp.data.code);
                    btn.siblings('.sac-affiliate-link').attr('href', resp.data.tracking);
                }
                btn.text('Generated! Copy Code').prop('disabled', false);
            });
        });
    });
    </script>
    <style>
    .sac-coupon-container { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 8px; }
    .sac-coupon-code { font-size: 24px; font-weight: bold; color: #0073aa; margin: 10px 0; background: white; padding: 10px; border: 1px solid #ddd; }
    .sac-generate-btn, .sac-affiliate-link { display: inline-block; padding: 10px 20px; margin: 5px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; }
    .sac-generate-btn:hover, .sac-affiliate-link:hover { background: #005a87; }
    </style>
    <?php
}
add_action('wp_footer', 'sac_inline_js');
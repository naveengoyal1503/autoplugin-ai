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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => '',
            'discount' => '10%',
            'product' => 'Product',
            'link' => '#',
        ), $atts);

        ob_start();
        ?>
        <div id="acv-coupon-<?php echo esc_attr($atts['affiliate_id']); ?>" class="acv-coupon" data-affiliate-id="<?php echo esc_attr($atts['affiliate_id']); ?>">
            <h3><?php echo esc_html($atts['product']); ?> - Save <?php echo esc_html($atts['discount']); ?>!</h3>
            <div class="acv-code" id="acv-code-<?php echo esc_attr($atts['affiliate_id']); ?>">Click to generate code</div>
            <a href="<?php echo esc_url($atts['link']); ?>" target="_blank" class="acv-button">Shop Now <span class="acv-tracking"><?php echo esc_attr($atts['affiliate_id']); ?></span></a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $code = 'ACV' . $affiliate_id . '-' . wp_generate_password(8, false);
        
        // Track usage
        $tracks = get_option('acv_tracks', array());
        $tracks[$affiliate_id] = isset($tracks[$affiliate_id]) ? $tracks[$affiliate_id] + 1 : 1;
        update_option('acv_tracks', $tracks);

        wp_send_json_success(array('code' => $code));
    }

    public function activate() {
        add_option('acv_installed', time());
    }
}

// Enqueue JS inline for single file
add_action('wp_footer', function() {
    if (!wp_script_is('acv-script', 'enqueued')) return;
    ?>
    <script>jQuery(document).ready(function($) {
        $('.acv-coupon').on('click', '.acv-code', function() {
            var $this = $(this);
            var affiliateId = $this.closest('.acv-coupon').data('affiliate-id');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_generate_coupon',
                nonce: acv_ajax.nonce,
                affiliate_id: affiliateId
            }, function(res) {
                if (res.success) {
                    $this.text(res.data.code).addClass('generated');
                }
            });
        });
        $('.acv-tracking').on('click', function() {
            // Track click
            gtag && gtag('event', 'coupon_click', {'affiliate_id': $(this).text()});
        });
    });</script>
    <style>
    .acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
    .acv-code { background: #fff; padding: 10px; margin: 10px 0; cursor: pointer; font-family: monospace; }
    .acv-code.generated { color: green; font-weight: bold; }
    .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; }
    </style>
    <?php
});

AffiliateCouponVault::get_instance();
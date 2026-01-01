/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost conversions.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-styles', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'coupon_code' => '',
            'discount' => '10%',
            'expires' => '+30 days',
            'button_text' => 'Get Coupon'
        ), $atts);

        $unique_id = uniqid('acv_');
        $tracking_id = $this->generate_tracking_id();

        ob_start();
        ?>
        <div class="acv-coupon-vault" id="<?php echo esc_attr($unique_id); ?>">
            <div class="acv-coupon-content">
                <h3>Exclusive Deal: <span class="acv-discount"><?php echo esc_html($atts['discount']); ?> OFF</span></h3>
                <p>Save big with our special coupon! Limited time only.</p>
                <div class="acv-coupon-code" style="display:none;"><?php echo esc_html($atts['coupon_code']); ?></div>
                <button class="acv-reveal-btn" data-tracking="<?php echo esc_attr($tracking_id); ?>"><?php echo esc_html($atts['button_text']); ?></button>
                <a href="<?php echo esc_url($atts['affiliate_url'] . '?coupon=' . $atts['coupon_code'] . '&ref=' . $tracking_id); ?>" class="acv-visit-btn" style="display:none;" target="_blank">Shop Now & Save</a>
                <div class="acv-expires">Expires: <?php echo date('M d, Y', strtotime($atts['expires'])); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_tracking_id() {
        return 'acv_' . uniqid() . '_' . time();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }

        $coupon_code = 'SAVE' . rand(1000, 9999);
        wp_send_json_success(array('code' => $coupon_code));
    }

    public function activate() {
        add_option('acv_activated', time());
        flush_rewrite_rules();
    }
}

// Assets would be base64 or inline in production single file
/*
Inline CSS:
.acv-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; }
.acv-discount { color: #e74c3c; font-weight: bold; }
.acv-reveal-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.acv-visit-btn { background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
*/

// Inline JS:
function acvFrontendScript(jQuery) {
    jQuery('.acv-reveal-btn').click(function() {
        var $container = jQuery(this).closest('.acv-coupon-vault');
        var code = $container.find('.acv-coupon-code').text();
        var tracking = jQuery(this).data('tracking');

        jQuery.post(acv_ajax.ajax_url, {
            action: 'acv_generate_coupon',
            nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $container.find('.acv-coupon-code').text(response.data.code);
                jQuery(this).hide();
                $container.find('.acv-visit-btn').show();
                // Track click
                console.log('Coupon revealed:', response.data.code, tracking);
            }
        });
    });
}(jQuery);

AffiliateCouponVault::get_instance();

// Pro upgrade notice
function acv_admin_notice() {
    if (!get_option('acv_pro_version')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');
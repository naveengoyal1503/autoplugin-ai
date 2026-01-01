/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupons with click tracking to boost your commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_track_coupon_click', array($this, 'track_coupon_click'));
        add_action('wp_ajax_nopriv_track_coupon_click', array($this, 'track_coupon_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            return; // Premium active
        }
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_filter('the_content', array($this, 'auto_insert_coupons'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-tracker', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => 'SAVE20',
            'afflink' => 'https://affiliate.com/?ref=yourid',
            'desc' => 'Get 20% off your purchase!',
            'brand' => 'Brand Name'
        ), $atts);

        $click_id = uniqid('sac_');
        $tracking_url = add_query_arg(array('sac_click' => $click_id, 'sac_code' => $atts['code']), $atts['afflink']);

        ob_start();
        ?>
        <div class="sac-coupon-box" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3><?php echo esc_html($atts['brand']); ?> Exclusive Coupon</h3>
            <p><strong>Code: <?php echo esc_html($atts['code']); ?></strong></p>
            <p><?php echo esc_html($atts['desc']); ?></p>
            <a href="#" class="sac-button sac-track" data-url="<?php echo esc_url($tracking_url); ?>" data-clickid="<?php echo esc_attr($click_id); ?>" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Redeem Now & Track Click</a>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">Tracked clicks help optimize your affiliate earnings.</p>
        </div>
        <script>
        jQuery('.sac-track[data-clickid="<?php echo esc_attr($click_id); ?>"]').on('click', function(e) {
            e.preventDefault();
            var url = jQuery(this).data('url');
            var clickid = jQuery(this).data('clickid');
            jQuery.post(sac_ajax.ajax_url, {
                action: 'track_coupon_click',
                clickid: clickid,
                nonce: sac_ajax.nonce
            }, function() {
                window.location.href = url;
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function auto_insert_coupons($content) {
        if (!is_single() || is_admin()) return $content;
        if (rand(1, 3) == 1) { // Insert 33% of the time
            $content .= do_shortcode('[sac_coupon code="SAVE20" afflink="https://example-affiliate.com" desc="Exclusive 20% discount!" brand="Example Brand"]');
        }
        return $content;
    }

    public function track_coupon_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sac_nonce')) {
            wp_die('Security check failed');
        }
        $click_id = sanitize_text_field($_POST['clickid']);
        $log = get_option('sac_click_logs', array()) + 1;
        update_option('sac_click_logs', $log);
        error_log('SAC Click tracked: ' . $click_id);
        wp_send_json_success('Tracked');
    }

    public function activate() {
        add_option('sac_installed', time());
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateCoupons::get_instance();

// Premium upsell notice
function sac_admin_notice() {
    if (!get_option('sac_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Coupons Pro</strong> for unlimited coupons, analytics dashboard, and custom domains! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'sac_admin_notice');

// Create tracker.js placeholder (in real plugin, include as asset)
function sac_tracker_js() {
    if (wp_script_is('sac-tracker', 'enqueued')) {
        ?>
        <script>
        // Inline tracker fallback
        </script>
        <?php
    }
}
add_action('wp_footer', 'sac_tracker_js');
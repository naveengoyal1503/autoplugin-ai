/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon-manager
 * Description: Automatically generates and displays personalized affiliate coupons with click tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCouponManager {
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
        add_shortcode('sacm_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_sacm_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sacm_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sacm_pro') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sacm-script', plugin_dir_url(__FILE__) . 'sacm.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sacm-script', 'sacm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'coupon_code' => 'SAVE20',
            'discount' => '20% OFF',
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'button_text' => 'Get Deal',
        ), $atts);

        $unique_id = uniqid('sacm_');
        ob_start();
        ?>
        <div class="sacm-coupon" id="<?php echo esc_attr($unique_id); ?>">
            <div class="sacm-coupon-code"><?php echo esc_html($atts['coupon_code']); ?></div>
            <div class="sacm-discount"><?php echo esc_html($atts['discount']); ?></div>
            <div class="sacm-expires">Expires: <?php echo esc_html($atts['expires']); ?></div>
            <a href="#" class="sacm-button" data-url="<?php echo esc_url($atts['affiliate_url']); ?>" data-id="<?php echo esc_attr($unique_id); ?>"><?php echo esc_html($atts['button_text']); ?></a>
            <div class="sacm-stats pro-only">Clicks: 0</div>
        </div>
        <style>
        .sacm-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 10px; }
        .sacm-coupon-code { font-size: 2em; font-weight: bold; color: #007cba; margin-bottom: 10px; }
        .sacm-discount { font-size: 1.2em; color: #28a745; margin-bottom: 5px; }
        .sacm-expires { font-size: 0.9em; color: #6c757d; margin-bottom: 15px; }
        .sacm-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .sacm-button:hover { background: #005a87; }
        .sacm-stats { margin-top: 10px; font-size: 0.8em; color: #6c757d; }
        .pro-only { display: none; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sacm_nonce')) {
            wp_die('Security check failed');
        }
        $url = sanitize_url($_POST['url']);
        $id = sanitize_text_field($_POST['id']);
        // In pro version, save to DB
        if (get_option('sacm_pro') === 'activated') {
            $clicks = get_option('sacm_clicks_' . $id, 0) + 1;
            update_option('sacm_clicks_' . $id, $clicks);
        }
        wp_redirect($url);
        exit;
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Coupon Manager:</strong> Unlock pro features like click tracking and unlimited coupons. <a href="https://example.com/pro" target="_blank">Upgrade now!</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateCouponManager::get_instance();

// JS file content (embedded for single file)
function sacm_add_inline_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.sacm-button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var url = button.data('url');
            var id = button.data('id');
            $.post(sacm_ajax.ajax_url, {
                action: 'sacm_track_click',
                url: url,
                id: id,
                nonce: '<?php echo wp_create_nonce('sacm_nonce'); ?>'
            }, function() {
                window.location.href = url;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sacm_add_inline_script');

// Pro activation simulation (manual via options)
// To activate pro: update_option('sacm_pro', 'activated');
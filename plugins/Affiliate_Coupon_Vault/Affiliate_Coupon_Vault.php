/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with click tracking to boost conversions.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'code' => 'SAVE20',
            'description' => 'Get 20% off with this exclusive coupon!',
            'expiry' => '',
            'brand' => 'Brand Name'
        ), $atts);

        if (empty($atts['affiliate_url'])) {
            return '<p>Missing affiliate URL.</p>';
        }

        $expiry = !empty($atts['expiry']) ? date('M d, Y', strtotime($atts['expiry'])) : 'No expiry';
        $unique_id = uniqid('acv_');

        ob_start();
        ?>
        <div class="acv-coupon" id="<?php echo esc_attr($unique_id); ?>">
            <h3><?php echo esc_html($atts['brand']); ?> Exclusive Coupon</h3>
            <p><?php echo esc_html($atts['description']); ?></p>
            <div class="acv-code"><?php echo esc_html($atts['code']); ?></div>
            <p>Expires: <?php echo esc_html($expiry); ?></p>
            <a href="#" class="acv-button" data-url="<?php echo esc_url($atts['affiliate_url']); ?>" data-id="<?php echo esc_attr($unique_id); ?>">Redeem Now (Tracked)</a>
            <div class="acv-stats" style="display:none;">
                Clicks: <span class="acv-clicks">0</span>
            </div>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
        .acv-code { background: #fff; font-size: 24px; font-weight: bold; padding: 10px; text-align: center; margin: 10px 0; border: 1px solid #ddd; }
        .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .acv-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }

        $url = sanitize_url($_POST['url']);
        $coupon_id = sanitize_text_field($_POST['id']);
        $clicks = get_option('acv_clicks_' . $coupon_id, 0) + 1;
        update_option('acv_clicks_' . $coupon_id, $clicks);

        if (get_option('acv_pro') === 'yes') {
            // Pro: Log to file or integrate with analytics
            error_log('ACV Pro Click: ' . $url . ' - Total clicks: ' . $clicks);
        }

        wp_redirect($url);
        exit;
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited coupons, advanced analytics, and API integrations! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }

    public function activate() {
        add_option('acv_pro', 'no');
    }
}

// Enqueue JS
add_action('wp_footer', function() {
    if (get_option('acv_pro') !== 'yes') {
        $clicks_limit = 5; // Free limit
    } else {
        $clicks_limit = 999;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-button').click(function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            var id = $(this).data('id');
            $.post(acv_ajax.ajaxurl, {
                action: 'acv_track_click',
                nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>',
                url: url,
                id: id
            }, function() {
                window.location.href = url;
            });
        });
    });
    </script>
    <?php
});

AffiliateCouponVault::get_instance();
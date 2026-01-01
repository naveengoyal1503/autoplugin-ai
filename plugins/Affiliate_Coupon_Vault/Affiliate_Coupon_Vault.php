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
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => '',
            'product' => 'Sample Product',
            'discount' => '20%',
            'link' => '#',
            'code' => ''
        ), $atts);

        $unique_code = $atts['code'] ?: $this->generate_unique_code($atts['affiliate_id']);

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3>Exclusive Deal: <strong><?php echo esc_html($atts['product']); ?></strong></h3>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?> OFF</strong></p>
            <p><strong>Code: <?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($atts['link'] . '?coupon=' . $unique_code); ?>" target="_blank" class="button acv-btn" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Get Deal Now (Affiliate Link)</a>
            <p style="font-size: 12px; margin-top: 10px;">Tracked clicks help us bring more deals!</p>
        </div>
        <script>
        jQuery('.acv-btn').on('click', function() {
            jQuery.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                coupon: '<?php echo esc_js($unique_code); ?>',
                nonce: acv_ajax.nonce
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function generate_unique_code($affiliate_id = '') {
        return substr(md5($affiliate_id . time() . rand()), 0, 8);
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $product = sanitize_text_field($_POST['product']);
        $discount = sanitize_text_field($_POST['discount']);
        $code = wp_generate_uuid4();
        wp_send_json_success(array('code' => $code, 'html' => $this->render_coupon_html($product, $discount, $code)));
    }

    private function render_coupon_html($product, $discount, $code) {
        return '<div class="generated-coupon">Code: ' . $code . ' for ' . $discount . ' off ' . $product . '</div>';
    }
}

// Track clicks
add_action('wp_ajax_acv_track_click', array(AffiliateCouponVault::get_instance(), 'track_click'));
add_action('wp_ajax_nopriv_acv_track_click', array(AffiliateCouponVault::get_instance(), 'track_click'));

function track_click() {
    check_ajax_referer('acv_nonce', 'nonce');
    $coupon = sanitize_text_field($_POST['coupon']);
    error_log('ACV Click tracked: ' . $coupon);
    wp_send_json_success('Tracked');
}

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', function() {
        echo '<h1>Affiliate Coupon Vault Settings</h1><p>Upgrade to Pro for analytics dashboard and unlimited coupons.</p>';
    });
});

// Custom JS (inline for single file)
add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script>jQuery(document).ready(function($) {
        $('.acv-generate').click(function() {
            $.post(acv_ajax.ajax_url, {
                action: 'acv_generate_coupon',
                product: $('#product').val(),
                discount: $('#discount').val(),
                nonce: acv_ajax.nonce
            }, function(res) {
                if (res.success) $('#coupon-output').html(res.data.html);
            });
        });
    });</script>
    <?php
});

AffiliateCouponVault::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like click analytics & templates for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});
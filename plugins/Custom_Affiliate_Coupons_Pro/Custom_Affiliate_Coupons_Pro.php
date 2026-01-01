/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/custom-affiliate-coupons
 * Description: Generate personalized affiliate coupons with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomAffiliateCouponsPro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('cac_coupon_display', array($this, 'coupon_display_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('custom-affiliate-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('cac-script', plugin_dir_url(__FILE__) . 'cac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('cac-script', 'cac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('cac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'cac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php _e('Custom Affiliate Coupons Pro', 'custom-affiliate-coupons'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cac_settings');
                do_settings_sections('cac_settings');
                submit_button();
                ?>
            </form>
            <h2>Generate Coupon</h2>
            <input type="text" id="coupon_affiliate" placeholder="Affiliate Link">
            <input type="text" id="coupon_code" placeholder="Coupon Code">
            <input type="number" id="coupon_discount" placeholder="Discount %">
            <button id="generate-coupon">Generate</button>
            <div id="coupon-output"></div>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('cac_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $affiliate = sanitize_url($_POST['affiliate']);
        $code = sanitize_text_field($_POST['code']);
        $discount = intval($_POST['discount']);
        $id = uniqid('cac_');

        $coupon = array(
            'id' => $id,
            'code' => $code,
            'affiliate' => $affiliate,
            'discount' => $discount,
            'uses' => 0,
            'expires' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'created' => current_time('mysql')
        );

        $coupons = get_option('cac_coupons', array());
        $coupons[$id] = $coupon;
        update_option('cac_coupons', $coupons);

        wp_send_json_success(array('shortcode' => '[cac_coupon_display id="' . $id . '"]', 'coupon' => $coupon));
    }

    public function coupon_display_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('cac_coupons', array());
        if (!isset($coupons[$atts['id']])) return '';

        $coupon = $coupons[$atts['id']];
        if (strtotime($coupon['expires']) < current_time('timestamp')) {
            return '<p>Coupon expired.</p>';
        }

        ob_start();
        ?>
        <div class="cac-coupon" style="border: 2px dashed #007cba; padding: 20px; text-align: center;">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($coupon['code']); ?></strong></h3>
            <p><?php echo $coupon['discount']; ?>% Off! <a href="<?php echo esc_url($coupon['affiliate']); ?><?php echo strpos($coupon['affiliate'], '?') === false ? '?coupon=' : '&coupon='; ?><?php echo esc_attr($coupon['code']); ?>" target="_blank" class="button">Redeem Now</a></p>
            <small>Used: <?php echo $coupon['uses']; ?> times. Expires: <?php echo $coupon['expires']; ?></small>
        </div>
        <script>
        jQuery('.cac-coupon a').click(function() {
            jQuery.post(cac_ajax.ajax_url, {action: 'track_coupon_use', id: '<?php echo $atts['id']; ?>', nonce: cac_ajax.nonce});
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('cac_settings', array());
    }
}

CustomAffiliateCouponsPro::get_instance();

// Track uses
add_action('wp_ajax_track_coupon_use', function() {
    check_ajax_referer('cac_nonce', 'nonce');
    $id = sanitize_text_field($_POST['id']);
    $coupons = get_option('cac_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['uses']++;
        update_option('cac_coupons', $coupons);
    }
    wp_die();
});

// Pro upsell notice
function cac_pro_notice() {
    if (!get_option('cac_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Affiliate Coupons Pro</strong> for unlimited coupons, analytics dashboard, and more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'cac_pro_notice');

// Enqueue JS
function cac_enqueue_js() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'cac_enqueue_js');
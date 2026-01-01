/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and auto-expiration to maximize conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('exclusive-coupons-pro', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON format: [{"code":"SAVE20","afflink":"https://affiliate.com","expiry":"2026-12-31","uses":"unlimited"}]</p>
                <p><input type="submit" name="ecp_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Upgrade to Pro for analytics, unlimited coupons, and custom designs. <a href="#pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $expiry = strtotime($coupon['expiry']);
        if ($expiry && $expiry < time()) {
            return '<div class="ecp-expired">Coupon expired!</div>';
        }
        $clicks = get_option('ecp_clicks_' . $atts['id'], 0);
        $uses_left = $coupon['uses'] === 'unlimited' ? 'Unlimited' : max(0, $coupon['uses'] - $clicks);
        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo $atts['id']; ?>">
            <h3>Exclusive Deal: <strong><?php echo esc_html($coupon['code']); ?></strong></h3>
            <p>Uses left: <?php echo $uses_left; ?></p>
            <a href="#" class="ecp-btn">Get Deal Now</a>
            <div class="ecp-track" style="display:none;"><?php echo esc_url($coupon['afflink']); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', json_encode(array(
                array('code' => 'WELCOME10', 'afflink' => '#', 'expiry' => '2026-06-30', 'uses' => '5')
            )));
        }
    }
}

ExclusiveCouponsPro::get_instance();

// AJAX for tracking
add_action('wp_ajax_ecp_track', 'ecp_track_click');
add_action('wp_ajax_nopriv_ecp_track', 'ecp_track_click');
function ecp_track_click() {
    check_ajax_referer('ecp_nonce', 'nonce');
    $id = intval($_POST['id']);
    $clicks = get_option('ecp_clicks_' . $id, 0) + 1;
    update_option('ecp_clicks_' . $id, $clicks);
    $coupons = json_decode(get_option('ecp_coupons', '[]'), true);
    if (isset($coupons[$id])) {
        if ($coupons[$id]['uses'] !== 'unlimited' && $clicks >= $coupons[$id]['uses']) {
            wp_send_json_error('Uses exhausted');
        }
    }
    wp_send_json_success($coupons[$id]['afflink']);
}

// Inline styles and scripts for single file
add_action('wp_head', 'ecp_styles');
function ecp_styles() {
    echo '<style>
.ecp-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
.ecp-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.ecp-btn:hover { background: #005a87; }
.ecp-expired { color: red; }
.pro-upsell { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; }
</style>';
}

add_action('wp_footer', 'ecp_scripts');
function ecp_scripts() {
    ?>
    <script>jQuery(document).ready(function($) {
        $('.ecp-btn').click(function(e) {
            e.preventDefault();
            var $coupon = $(this).closest('.ecp-coupon');
            var id = $coupon.data('id');
            var link = $coupon.find('.ecp-track').text();
            $.post(ecp_ajax.ajax_url, {action: 'ecp_track', id: id, nonce: ecp_ajax.nonce}, function(res) {
                if (res.success) {
                    window.open(res.data, '_blank');
                } else {
                    alert(res.data);
                }
            });
        });
    });</script>
    <?php
}

// Pro upsell notice
add_action('admin_notices', 'ecp_pro_notice');
function ecp_pro_notice() {
    if (get_option('ecp_pro_activated') !== 'yes') {
        echo '<div class="pro-upsell notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro</strong>: Unlimited coupons, analytics, and more! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
}

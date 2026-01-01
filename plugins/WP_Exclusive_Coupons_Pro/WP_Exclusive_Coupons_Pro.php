/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking, auto-expiration, and sharing to maximize affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons_Pro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wpec_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_scripts($hook) {
        if ('toplevel_page_wp-exclusive-coupons' !== $hook) {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-exclusive-coupons-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function admin_page() {
        if (isset($_POST['save_coupon']) && check_admin_referer('save_coupon_nonce')) {
            update_option('wpec_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', array());
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <p><strong>Pro Features Unlocked:</strong> Unlimited coupons, click tracking, auto-expiration. <a href="#" onclick="alert('Upgrade to Pro for $49/year!')">Upgrade Now</a></p>
            <form method="post">
                <?php wp_nonce_field('save_coupon_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td><textarea name="coupons" rows="10" cols="80" placeholder='[{"code":"SAVE20","afflink":"https://example.com","desc":"20% off","expires":"2026-12-31"}]'><?php echo esc_textarea(json_encode($coupons)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'save_coupon'); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[wpec_coupon id="0"]</code> to display coupon. IDs start from 0.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                $('.wpec-copy-btn').click(function() {
                    navigator.clipboard.writeText($(this).data('code'));
                    $(this).text('Copied!');
                });
            });
        ");
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('wpec_coupons', array());
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$id];
        $today = date('Y-m-d');
        if ($coupon['expires'] < $today) {
            return '<div class="wpec-expired">Coupon expired!</div>';
        }
        $click_id = uniqid();
        update_option('wpec_clicks_' . $click_id, time());
        ob_start();
        ?>
        <div class="wpec-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div class="wpec-code"><?php echo esc_html($coupon['code']); ?><button class="wpec-copy-btn button" data-code="<?php echo esc_attr($coupon['code']); ?>">Copy</button></div>
            <a href="<?php echo esc_url($coupon['afflink'] . (strpos($coupon['afflink'], '?') === false ? '?' : '&') . 'ref=wpec'); ?>" class="wpec-btn button-primary" target="_blank">Redeem Now (Affiliate Link)</a>
            <small>Expires: <?php echo esc_html($coupon['expires']); ?></small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', array(
                array('code' => 'WELCOME10', 'afflink' => 'https://example.com', 'desc' => '10% Off First Purchase', 'expires' => '2026-06-30')
            ));
        }
    }
}

WP_Exclusive_Coupons_Pro::get_instance();

// Pro Upsell Notice
function wpec_pro_upsell() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>WP Exclusive Coupons Pro:</strong> Unlock unlimited coupons & tracking for $49/year! <a href="https://example.com/upgrade">Upgrade</a></p></div>';
    }
}
add_action('admin_notices', 'wpec_pro_upsell');

// CSS
add_action('wp_head', function() { ?>
<style>
.wpec-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
.wpec-code { font-size: 24px; font-weight: bold; color: #0073aa; margin: 10px 0; }
.wpec-copy-btn, .wpec-btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
.wpec-copy-btn { background: #eee; }
.wpec-expired { background: #ffebee; color: #c62828; padding: 20px; text-align: center; }
</style>
<?php });

// Admin CSS placeholder
/* admin.css content would go here if separate file */
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate exclusive affiliate coupons, track usage, and boost conversions with custom promo codes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPExclusiveCoupons {
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
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('wpec_coupons', array());
        add_option('wpec_settings', array('tracking' => true));
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'wpec-coupons',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_coupons', sanitize_text_field($_POST['coupons']));
            update_option('wpec_settings', array('tracking' => true));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', array());
        $settings = get_option('wpec_settings', array());
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Add Coupon</th>
                        <td>
                            <input type="text" name="coupons" value="<?php echo esc_attr($coupons ? implode(';', $coupons) : ''); ?>" placeholder="code1=afflink1;code2=afflink2" class="regular-text" /><br>
                            <small>Format: code=affiliate_url (separate with ;)</small>
                        </td>
                    </tr>
                    <tr>
                        <th>Enable Tracking</th>
                        <td><input type="checkbox" name="tracking" <?php checked($settings['tracking']); ?> /></td>
                    </tr>
                </table>
                <p><input type="submit" name="wpec_save" class="button-primary" value="Save Coupons" /></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[wpec_coupon code="YOURCODE"]</code></p>
            <h2>Stats</h2>
            <p>Total Clicks: <?php echo get_option('wpec_clicks', 0); ?></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-script', plugin_dir_url(__FILE__) . 'wpec.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupons = get_option('wpec_coupons', array());
        parse_str(str_replace(';', '&', $coupons), $coupon_map);
        if (isset($coupon_map[$atts['code']])) {
            $url = $coupon_map[$atts['code']];
            $click_id = uniqid();
            if (get_option('wpec_settings')['tracking']) {
                $url = add_query_arg('wpec_ref', $click_id, $url);
                // Log click
                $clicks = get_option('wpec_clicks', 0) + 1;
                update_option('wpec_clicks', $clicks);
            }
            return '<a href="' . esc_url($url) . '" class="wpec-coupon-btn" target="_blank">Use ' . esc_html($atts['code']) . ' (Exclusive Deal!)</a>';
        }
        return 'Invalid coupon code.';
    }
}

WPExclusiveCoupons::get_instance();

// AJAX for premium tracking (free version stub)
add_action('wp_ajax_wpec_track', 'wpec_track_ajax');
function wpec_track_ajax() {
    if (current_user_can('manage_options')) {
        $clicks = get_option('wpec_clicks', 0) + 1;
        update_option('wpec_clicks', $clicks);
        wp_die();
    }
}

// Premium upsell notice
add_action('admin_notices', function() {
    if (!get_option('wpec_premium_activated')) {
        echo '<div class="notice notice-info"><p>Unlock unlimited coupons and advanced analytics with <strong>WP Exclusive Coupons Pro</strong> for $49/year! <a href="https://example.com/premium">Upgrade Now</a></p></div>';
    }
});
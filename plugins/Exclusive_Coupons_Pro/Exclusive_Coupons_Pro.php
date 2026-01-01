/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('exclusive_coupons_pro_options', 'exclusive_coupons_pro_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'exclusive_coupons');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'exclusive_coupons', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('exclusive_coupons_pro_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array(
            array('code' => 'SAVE20', 'afflink' => '', 'desc' => '20% off on all products')
        );
        echo '<textarea name="exclusive_coupons_pro_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: {"code":"CODE","afflink":"URL","desc":"Description"}</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('exclusive_coupons_pro_options');
                do_settings_sections('exclusive_coupons');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiry, and custom designs for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('exclusive_coupons_pro_settings', array());
        $coupons = json_decode($settings['coupons'] ?? '[]', true);
        if (isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
            return '<div class="exclusive-coupon-pro" style="border:2px solid #007cba;padding:20px;background:#f9f9f9;border-radius:5px;"><h3>Exclusive Deal: <strong>' . esc_html($coupon['code']) . '</strong></h3><p>' . esc_html($coupon['desc']) . '</p><a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="coupon-btn" style="background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:3px;">Get Deal Now (Affiliate)</a></div>';
        }
        return '';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function activate() {
        if (!get_option('exclusive_coupons_pro_settings')) {
            update_option('exclusive_coupons_pro_settings', array('coupons' => json_encode(array(array('code' => 'WELCOME10', 'afflink' => 'https://example.com/aff', 'desc' => '10% off first purchase')))));
        }
    }
}

new ExclusiveCouponsPro();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to Pro for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
});

// Prevent direct access to style.css if not exists
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '.exclusive-coupon-pro { max-width: 400px; margin: 20px 0; } .coupon-btn:hover { background: #005a87; }');
}

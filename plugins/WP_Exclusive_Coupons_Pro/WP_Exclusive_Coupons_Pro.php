/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and displays personalized, trackable coupon codes from affiliate programs to boost conversions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_footer', array($this, 'inline_styles'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function admin_menu() {
        add_options_page(
            'WP Exclusive Coupons',
            'Exclusive Coupons',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('wp_exclusive_coupons_options', 'wp_exclusive_coupons_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'wp-exclusive-coupons');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'wp-exclusive-coupons', 'main_section');
        add_settings_field('pro_notice', 'Go Pro', array($this, 'pro_notice'), 'wp-exclusive-coupons', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('wp_exclusive_coupons_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array(
            array('name' => 'Sample Deal', 'code' => 'SAVE20', 'afflink' => '#', 'desc' => '20% off on premium tools')
        );
        echo '<textarea name="wp_exclusive_coupons_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Deal Name","code":"CODE10","afflink":"https://aff.link","desc":"Description"}</p>';
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Pro Features:</strong> Unlimited coupons, click tracking, custom designs, auto-expiry, premium integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/yr)</a></p></div>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Exclusive Coupons Settings', 'wp-exclusive-coupons'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_exclusive_coupons_options');
                do_settings_sections('wp-exclusive-coupons');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if (!is_admin()) {
            wp_enqueue_style('wp-exclusive-coupons', plugins_url('style.css', __FILE__), array(), '1.0.0');
        }
    }

    public function inline_styles() {
        ?>
        <style>
        .exclusive-coupon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            margin: 20px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }
        .exclusive-coupon h3 { margin: 0 0 10px; font-size: 1.5em; }
        .coupon-code { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; font-size: 1.8em; font-weight: bold; display: inline-block; margin: 10px 0; }
        .coupon-desc { margin: 10px 0; opacity: 0.9; }
        .coupon-btn { background: #ff6b6b; color: white; padding: 12px 30px; border: none; border-radius: 50px; font-size: 1.1em; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .coupon-btn:hover { background: #ff5252; transform: translateY(-2px); }
        @keyframes pulse { 0% { box-shadow: 0 10px 30px rgba(0,0,0,0.3); } 50% { box-shadow: 0 10px 40px rgba(102,126,234,0.6); } 100% { box-shadow: 0 10px 30px rgba(0,0,0,0.3); } }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('wp_exclusive_coupons_settings', array());
        $coupons = isset($settings['coupons']) ? json_decode($settings['coupons'], true) : array();
        if (empty($coupons)) return '';

        $coupon = $coupons[array_rand($coupons)];
        if (!$coupon) return '';

        $unique_id = uniqid('coupon_');
        return '<div id="' . $unique_id . '" class="exclusive-coupon" onclick="trackCouponClick(this)">
                    <h3>🔥 Exclusive Deal Just For You!</h3>
                    <div class="coupon-code">' . esc_html($coupon['code']) . '</div>
                    <div class="coupon-desc">' . esc_html($coupon['desc']) . '</div>
                    <a href="' . esc_url($coupon['afflink']) . '" class="coupon-btn" target="_blank">Grab Deal Now</a>
                </div>
                <script>
                function trackCouponClick(el) {
                    gtag("event", "coupon_click", {"coupon_code": el.querySelector(".coupon-code").textContent});
                    console.log("Coupon clicked: " + el.querySelector(".coupon-code").textContent);
                }
                </script>';
    }

    public function activate() {
        add_option('wp_exclusive_coupons_settings', array());
    }
}

WPExclusiveCoupons::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('wp_exclusive_coupons_pro_activated')) {
        echo '<div class="notice notice-success is-dismissible"><p>🚀 <strong>WP Exclusive Coupons Pro:</strong> Unlock unlimited coupons & tracking! <a href="https://example.com/pro">Upgrade for $49/yr</a></p></div>';
    }
});
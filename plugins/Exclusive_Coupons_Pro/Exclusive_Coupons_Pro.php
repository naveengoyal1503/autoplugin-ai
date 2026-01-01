/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
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
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('exclusive_coupons_group', 'exclusive_coupons_options');
        add_settings_section('main_section', 'Coupon Settings', null, 'exclusive_coupons');

        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'exclusive_coupons', 'main_section');
        add_settings_field('pro_notice', 'Go Pro', array($this, 'pro_notice'), 'exclusive_coupons', 'main_section');
    }

    public function coupons_field() {
        $options = get_option('exclusive_coupons_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        echo '<textarea name="exclusive_coupons_options[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">Enter JSON array of coupons: {"name":"Discount Name","code":"PROMO10","afflink":"https://affiliate.link","desc":"10% off"}</p>';
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Pro Version:</strong> Unlimited coupons, analytics, auto-generation, custom designs. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('exclusive_coupons_group');
                do_settings_sections('exclusive_coupons');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('exclusive_coupons_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        $id = intval($atts['id']);
        if (isset($coupons[$id])) {
            $coupon = $coupons[$id];
            $unique_code = $coupon['code'] . '-' . uniqid();
            return '<div class="exclusive-coupon" style="border:2px solid #007cba; padding:20px; margin:20px 0; background:#f9f9f9;"><h3>' . esc_html($coupon['name']) . '</h3><p>' . esc_html($coupon['desc']) . '</p><p><strong>Your Exclusive Code:</strong> <code>' . esc_html($unique_code) . '</code></p><a href="' . esc_url($coupon['afflink']) . '" class="button button-primary" target="_blank">Get Deal Now &rsaquo;</a></div>';
        }
        return 'Coupon not found.';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function activate() {
        add_option('exclusive_coupons_options', array('coupons' => array(
            array('name' => 'Sample 10% Off', 'code' => 'WELCOME10', 'afflink' => '#', 'desc' => 'Exclusive 10% discount for our readers')
        )));
    }
}

new ExclusiveCouponsPro();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('exclusive_coupons_pro_activated')) {
        echo '<div class="notice notice-success is-dismissible"><p>Unlock <strong>Exclusive Coupons Pro</strong> features: Unlimited coupons, tracking & more! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
});

// Dummy style.css content (in real plugin, separate file)
/*
.exclusive-coupon { max-width: 400px; }
.exclusive-coupon code { background: #fff; padding: 5px 10px; }
*/
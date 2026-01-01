/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons, personalized discounts, and deal trackers to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        add_shortcode('acv_deals', array($this, 'deals_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv-settings');
        add_settings_field('acv_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'acv-settings', 'acv_main');
        add_settings_field('acv_coupons', 'Default Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function api_key_field() {
        $options = get_option('acv_options');
        echo '<input type="text" name="acv_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network API key (e.g., for CJ, ShareASale).</p>';
    }

    public function coupons_field() {
        $options = get_option('acv_options');
        $coupons = $options['coupons'] ?? json_encode(array(
            array('code' => 'SAVE20', 'afflink' => '#', 'desc' => '20% off first purchase'),
            array('code' => 'DEAL10', 'afflink' => '#', 'desc' => '10% sitewide')
        ));
        echo '<textarea name="acv_options[coupons]" rows="5" cols="50">' . esc_textarea($coupons) . '</textarea>';
        echo '<p class="description">JSON array of coupons: [{ "code": "CODE", "afflink": "URL", "desc": "Description" }]</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $options = get_option('acv_options');
        $coupons = json_decode($options['coupons'] ?? '[]', true);
        if (empty($coupons)) return '';

        $output = '<div class="acv-coupon-vault">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="acv-coupon">';
            $output .= '<h4>' . esc_html($coupon['desc']) . '</h4>';
            $output .= '<span class="acv-code">' . esc_html($coupon['code']) . '</span>';
            $output .= '<a href="' . esc_url($coupon['afflink']) . '" class="acv-button" target="_blank">Shop Now & Save</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function deals_shortcode($atts) {
        return '<div class="acv-deals">Exclusive Deals: Use [acv_coupon] shortcode for coupons! <a href="#" class="acv-track">Track Clicks</a></div>';
    }

    public function activate() {
        if (!get_option('acv_options')) {
            update_option('acv_options', array('coupons' => json_encode(array(
                array('code' => 'WELCOME15', 'afflink' => 'https://example-affiliate.com/?ref=123', 'desc' => '15% off for new users')
            ))));
        }
    }
}

// Create CSS file content (self-contained, but in real use, separate file)
function acv_add_inline_style() {
    $css = ".acv-coupon-vault { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; } .acv-coupon { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; } .acv-code { font-size: 24px; font-weight: bold; color: #e74c3c; background: #fff; padding: 10px; display: block; } .acv-button { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; } .acv-button:hover { background: #2ecc71; }";
    wp_add_inline_style('acv-style', $css);
}
add_action('wp_enqueue_scripts', 'acv_add_inline_style');

// JS for tracking
function acv_add_inline_script() {
    $script = "jQuery(document).ready(function($) { $('.acv-button').on('click', function(e) { var link = $(this).attr('href'); gtag('event', 'coupon_click', {'event_category': 'affiliate', 'event_label': link}); }); });";
    wp_add_inline_script('acv-script', $script);
}
add_action('wp_enqueue_scripts', 'acv_add_inline_script');

AffiliateCouponVault::get_instance();

// Pro upgrade notice
function acv_pro_notice() {
    if (!is_super_admin()) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Pro features</strong> like unlimited coupons and analytics: <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');
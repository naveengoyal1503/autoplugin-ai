/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons for your blog posts, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_head', array($this, 'add_coupon_style'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        if (has_shortcode(get_post()->post_content, 'affiliate_coupon')) {
            wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        }
    }

    public function add_coupon_style() {
        if (has_shortcode(get_post()->post_content, 'affiliate_coupon')) {
            echo '<style>.acv-coupon { background: #fff3cd; border: 2px dashed #ffc107; padding: 20px; margin: 20px 0; text-align: center; border-radius: 10px; }.acv-code { font-size: 24px; font-weight: bold; color: #dc3545; background: #ffeaa7; padding: 10px; border-radius: 5px; display: inline-block; margin: 10px 0; }.acv-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }</style>';
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => 'SAVE20',
            'afflink' => '#',
            'desc' => 'Get 20% off your purchase!',
            'expires' => date('Y-m-d', strtotime('+30 days')),
        ), $atts);

        $output = '<div class="acv-coupon">';
        $output .= '<h3>Exclusive Coupon!</h3>';
        $output .= '<p>' . esc_html($atts['desc']) . '</p>';
        $output .= '<div class="acv-code">' . esc_html($atts['code']) . '</div>';
        $output .= '<p>Expires: ' . esc_html($atts['expires']) . '</p>';
        $output .= '<a href="' . esc_url($atts['afflink']) . '" class="acv-btn" target="_blank">Shop Now & Save</a>';
        $output .= '</div>';

        return $output;
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_pro_key');
        add_settings_section('acv_main', 'Pro Upgrade', null, 'acv');
        add_settings_field('pro_key', 'Pro License Key', array($this, 'pro_key_field'), 'acv', 'acv_main');
    }

    public function pro_key_field() {
        $key = get_option('acv_pro_key');
        echo '<input type="text" name="acv_pro_key" value="' . esc_attr($key) . '" class="regular-text" />';
        echo '<p class="description">Enter Pro key for unlimited coupons and analytics. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon code="SAVE20" afflink="https://aff.link" desc="20% off!" expires="2026-02-01"]</code></p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, auto-generation. Upgrade for profitability!</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Pro nag
add_action('admin_notices', function() {
    if (!get_option('acv_pro_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons & analytics! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade Now</a></p></div>';
    }
});
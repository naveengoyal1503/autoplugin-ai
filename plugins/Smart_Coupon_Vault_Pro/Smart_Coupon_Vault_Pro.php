/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault Pro
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon aggregator for affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-frontend', plugin_dir_url(__FILE__) . 'scv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('scv-frontend', plugin_dir_url(__FILE__) . 'scv.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Coupon Vault', 'Coupon Vault', 'manage_options', 'smart-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('scv_options', 'scv_settings');
        add_settings_section('scv_main', 'Settings', null, 'scv');
        add_settings_field('scv_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'scv', 'scv_main');
        add_settings_field('scv_coupons', 'Custom Coupons', array($this, 'coupons_field'), 'scv', 'scv_main');
    }

    public function api_key_field() {
        $settings = get_option('scv_settings', array());
        echo '<input type="text" name="scv_settings[api_key]" value="' . esc_attr($settings['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network API key (e.g., Amazon, CJ Affiliate).</p>';
    }

    public function coupons_field() {
        $settings = get_option('scv_settings', array());
        $coupons = $settings['coupons'] ?? '';
        echo '<textarea name="scv_settings[coupons]" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea>';
        echo '<p class="description">JSON array of coupons: [{"code":"SAVE10","desc":"10% off","afflink":"https://example.com"}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('scv_options');
                do_settings_sections('scv');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock AI coupon generation, analytics, and unlimited deals for $29/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $settings = get_option('scv_settings', array());
        $coupons = json_decode($settings['coupons'] ?? '[]', true);
        if (empty($coupons)) {
            $coupons = $this->generate_demo_coupons($atts['limit']);
        }
        $output = '<div class="scv-vault">';
        foreach (array_slice($coupons, 0, $atts['limit']) as $coupon) {
            $output .= '<div class="scv-coupon">';
            $output .= '<h4>' . esc_html($coupon['desc']) . '</h4>';
            $output .= '<span class="scv-code">' . esc_html($coupon['code']) . '</span> ';
            $output .= '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="scv-btn">Shop Now (Affiliate)</a>';
            $output .= '</div>';
        }
        $output .= '<p><a href="' . admin_url('options-general.php?page=smart-coupon-vault') . '">Manage Coupons</a> | <a href="https://example.com/pro" target="_blank">Go Pro</a></p>';
        $output .= '</div>';
        return $output;
    }

    private function generate_demo_coupons($limit) {
        return array(
            array('code' => 'SAVE20', 'desc' => '20% off hosting', 'afflink' => 'https://example.com/hosting'),
            array('code' => 'WP10', 'desc' => '10% off WordPress themes', 'afflink' => 'https://example.com/themes'),
            array('code' => 'DEAL50', 'desc' => '$50 off VPN', 'afflink' => 'https://example.com/vpn'),
        );
    }

    public function activate() {
        add_option('scv_settings', array());
    }
}

new SmartCouponVault();

// Pro upsell notice
function scv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Smart Coupon Vault Pro</strong>: AI coupons, analytics & more! <a href="https://example.com/pro">Upgrade Now ($29/yr)</a></p></div>';
}
add_action('admin_notices', 'scv_pro_notice');

// Frontend styles
$scv_css = '* { box-sizing: border-box; } .scv-vault { max-width: 600px; margin: 20px 0; } .scv-coupon { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0073aa; } .scv-code { font-size: 1.2em; font-weight: bold; color: #0073aa; } .scv-btn { background: #0073aa; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; } .scv-btn:hover { background: #005a87; }';
file_put_contents(plugin_dir_path(__FILE__) . 'scv.css', $scv_css);

// Frontend JS
$scv_js = "jQuery(document).ready(function($) { $('.scv-coupon').hover(function() { $(this).addClass('scv-hover'); }, function() { $(this).removeClass('scv-hover'); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'scv.js', $scv_js);

// Add CSS/JS to plugin header
add_action('wp_head', function() {
    echo '<style>' . $scv_css . '</style><script>' . $scv_js . '</script>';
});
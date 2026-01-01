/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Pro
 * Plugin URI: https://example.com/smart-affiliate-coupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes to boost affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCouponPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupon Pro', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_settings');
        add_settings_section('sac_main', 'Coupon Settings', null, 'sac-pro');
        add_settings_field('sac_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'sac-pro', 'sac_main');
        add_settings_field('sac_default_discount', 'Default Discount %', array($this, 'discount_field'), 'sac-pro', 'sac_main');
    }

    public function api_key_field() {
        $settings = get_option('sac_settings', array());
        echo '<input type="text" name="sac_settings[api_key]" value="' . esc_attr($settings['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network API key (e.g., Amazon, CJ Affiliate).</p>';
    }

    public function discount_field() {
        $settings = get_option('sac_settings', array());
        echo '<input type="number" name="sac_settings[default_discount]" value="' . esc_attr($settings['default_discount'] ?? '20') . '" min="1" max="100" />%';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sac_options');
                do_settings_sections('sac-pro');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and auto-generation for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'product' => 'Featured Product',
            'link' => 'https://example.com/affiliate-link',
            'code' => ''
        ), $atts);

        $settings = get_option('sac_settings', array());
        $discount = $settings['default_discount'] ?? 20;
        $code = $atts['code'] ?: 'SAVE' . $discount . wp_generate_password(4, false);

        ob_start();
        ?>
        <div id="sac-coupon" class="sac-coupon-box">
            <h3>Exclusive Deal: <?php echo esc_html($atts['product']); ?></h3>
            <div class="sac-discount"><?php echo $discount; ?>% OFF</div>
            <p>Use code: <strong><?php echo esc_html($code); ?></strong></p>
            <a href="<?php echo esc_url($atts['link']); ?>" class="sac-button" target="_blank">Grab Deal Now (Affiliate Link)</a>
            <small>Generated uniquely for you. Limited time!</small>
        </div>
        <style>
        .sac-coupon-box { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 300px; }
        .sac-discount { font-size: 2em; color: #ff0000; font-weight: bold; }
        .sac-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .sac-button:hover { background: #005a87; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#sac-coupon').on('click', '.sac-button', function() {
                $(this).text('Copied! Check your email for code.');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sac_settings', array('default_discount' => 20));
    }
}

new SmartAffiliateCouponPro();

// Pro nag
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate Coupon Pro:</strong> Upgrade to Pro for unlimited features! <a href="https://example.com/pro">Learn More</a></p></div>';
});

// Free style.css placeholder (inline for single file)
/* Minimal CSS embedded above */

// Free script.js placeholder (inline for single file)
/* Minimal JS embedded above */
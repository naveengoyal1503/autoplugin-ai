/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons, personalized discounts, and promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'acv_options');
        add_settings_section('acv_main_section', 'Main Settings', null, 'affiliate-coupon-vault');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'acv_main_section');
        add_settings_field('acv_pro_notice', 'Go Pro', array($this, 'pro_notice'), 'affiliate-coupon-vault', 'acv_main_section');
    }

    public function coupons_field() {
        $options = get_option('acv_options', array('coupons' => array(
            array('title' => 'Sample Deal', 'code' => 'SAVE20', 'afflink' => '#', 'expires' => ''),
        )));
        $coupons = $options['coupons'];
        echo '<textarea name="acv_options[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"title":"Deal Title","code":"CODE10","afflink":"https://affiliate.link","expires":"2026-12-31"}</p>';
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Upgrade to Pro</strong> for unlimited coupons, analytics, auto-expiration, and premium integrations! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $options = get_option('acv_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        if (empty($coupons)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';

        $html = '<div class="acv-vault">';
        foreach ($coupons as $coupon) {
            $expires = !empty($coupon['expires']) ? 'Expires: ' . date('M j, Y', strtotime($coupon['expires'])) : '';
            $html .= '<div class="acv-coupon">
                <h3>' . esc_html($coupon['title']) . '</h3>
                <div class="acv-code">' . esc_html($coupon['code']) . '</div>
                <p>' . $expires . '</p>
                <a href="' . esc_url($coupon['afflink']) . '" class="acv-button" target="_blank">Get Deal (Affiliate)</a>
            </div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function activate() {
        add_option('acv_options', array('coupons' => array()));
    }
}

AffiliateCouponVault::get_instance();

/* Dummy CSS - In real plugin, create style.css */
/*
.acv-vault { display: flex; flex-wrap: wrap; gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; flex: 1 1 300px; }
.acv-code { font-size: 2em; font-weight: bold; color: #e74c3c; background: #fff; padding: 10px; border-radius: 5px; text-align: center; }
.acv-button { display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.acv-button:hover { background: #219a52; }
*/

/* Dummy JS - In real plugin, create script.js */
/*
jQuery(document).ready(function($) {
    $('.acv-coupon').on('click', '.acv-code', function() {
        navigator.clipboard.writeText($(this).text());
        $(this).after('<span style="color: green;"> Copied!</span>');
    });
});
*/
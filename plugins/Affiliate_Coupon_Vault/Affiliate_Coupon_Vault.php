/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from popular networks to boost your affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) return;
        $this->coupons = get_option('acv_coupons', array(
            array('code' => 'SAVE20', 'desc' => '20% off on all products', 'afflink' => '#'),
            array('code' => 'WELCOME10', 'desc' => '10% off first purchase', 'afflink' => '#'),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping today', 'afflink' => '#')
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 3), $atts);
        $html = '<div class="acv-vault">';
        $shown = 0;
        foreach ($this->coupons as $coupon) {
            if ($shown >= $atts['limit']) break;
            $html .= '<div class="acv-coupon">';
            $html .= '<h4>' . esc_html($coupon['code']) . '</h4>';
            $html .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $html .= '<a href="' . esc_url($coupon['afflink']) . '" class="acv-button" target="_blank">Get Deal</a>';
            $html .= '</div>';
            $shown++;
        }
        $html .= '</div>';
        $html .= '<p><small>Pro version unlocks premium networks & analytics. <a href="#pro">Upgrade Now</a></small></p>';
        return $html;
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Manage Coupons', null, 'acv-settings');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<textarea name="acv_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"code":"SAVE20","desc":"20% off","afflink":"https://aff.link"}</p>';
    }

    public function settings_page() {
        ?><div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><?php
        settings_errors();
        ?><form method="post" action="options.php"><?php
        settings_fields('acv_settings');
        do_settings_sections('acv-settings');
        submit_button();
        ?></form></div><?php
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array(
                array('code' => 'SAVE20', 'desc' => '20% off on all products', 'afflink' => ''),
                array('code' => 'WELCOME10', 'desc' => '10% off first purchase', 'afflink' => ''),
                array('code' => 'FREESHIP', 'desc' => 'Free shipping today', 'afflink' => '')
            ));
        }
    }
}

new AffiliateCouponVault();

/* CSS - Inline for single file */
function acv_add_inline_css() {
    ?><style>
.acv-vault { display: flex; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
.acv-coupon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); flex: 1 1 300px; text-align: center; }
.acv-coupon h4 { margin: 0 0 10px; font-size: 24px; }
.acv-coupon p { margin: 0 0 15px; font-size: 14px; }
.acv-button { background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
.acv-button:hover { background: #ff5252; }
@media (max-width: 768px) { .acv-vault { flex-direction: column; } }
</style><?php
}
add_action('wp_head', 'acv_add_inline_css');
add_action('wp_footer', 'acv_add_inline_css');
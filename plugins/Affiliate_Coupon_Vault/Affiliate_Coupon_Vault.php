/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array();
        echo '<textarea name="affiliate_coupon_vault_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">Enter JSON array of coupons: {"name":"Discount","code":"SAVE20","afflink":"https://aff.link","expiry":"2026-12-31"}</p>';
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
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? json_decode($settings['coupons'], true) : array();
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Configure now</a>.</p>';
        }
        $coupon = $coupons ?? $coupons;
        $today = date('Y-m-d');
        if (isset($coupon['expiry']) && $coupon['expiry'] < $today) {
            return '<p>This coupon has expired.</p>';
        }
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <h3><?php echo esc_html($coupon['name'] ?? 'Exclusive Deal'); ?></h3>
            <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($coupon['code'] ?? ''); ?></span></p>
            <a href="<?php echo esc_url($coupon['afflink'] ?? '#'); ?}" target="_blank" class="coupon-button" rel="nofollow">Get Deal Now (Affiliate Link)</a>
        </div>
        <style>
        .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px; }
        .coupon-code { font-size: 24px; color: #e74c3c; font-weight: bold; }
        .coupon-button { display: inline-block; background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .coupon-button:hover { background: #c0392b; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('coupons' => json_encode(array(
            array('name' => 'Sample 20% Off', 'code' => 'SAVE20', 'afflink' => '#', 'expiry' => '2026-12-31')
        ))));
    }
}

new AffiliateCouponVault();

// Inline styles and scripts
add_action('wp_head', function() {
    echo '<style>.affiliate-coupon-vault{max-width:400px;margin:20px auto;border:2px dashed #007cba;padding:20px;background:#f9f9f9;text-align:center;}.coupon-code{font-size:24px;color:#e74c3c;font-weight:bold;}.coupon-button{display:inline-block;background:#e74c3c;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;}.coupon-button:hover{background:#c0392b;}</style>';
});

add_action('wp_footer', function() {
    echo '<script>jQuery(document).ready(function($){$(".coupon-button").on("click",function(){$(this).text("Copied! Grab it!");});$(".coupon-code").on("click",function(){var code=$(this).text();navigator.clipboard.writeText(code).then(function(){$(this).css("color","#27ae60");setTimeout(function(){$(".coupon-code").css("color","#e74c3c");},2000);});});});</script>';
});
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays personalized affiliate coupons and exclusive deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_api_keys');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(get_option('acv_coupons', '{"coupon1":{"code":"SAVE20","desc":"20% off","afflink":"https://aff.link/1","expiry":"2026-12-31"}}')); ?></textarea>
                            <p class="description">JSON format: {"id":{"code":"CODE","desc":"Description","afflink":"Affiliate Link","expiry":"YYYY-MM-DD"}}</p>
                        </td>
                    </tr>
                    <tr>
                        <th>API Keys (Premium)</th>
                        <td><input type="text" name="acv_api_keys" value="<?php echo esc_attr(get_option('acv_api_keys', '')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        if (!isset($coupons[$atts['id']])) {
            return '<p>Coupon not found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $today = date('Y-m-d');
        if ($coupon['expiry'] < $today) {
            return '<div class="acv-coupon expired">Coupon expired.</div>';
        }
        ob_start();
        ?>
        <div class="acv-coupon">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div class="acv-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?>" class="acv-button" target="_blank">Get Deal</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', '{"welcome":{"code":"WELCOME10","desc":"10% off first purchase","afflink":"https://example.com/aff","expiry":"2026-12-31"}}');
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline styles and scripts for single file

function acv_inline_assets() {
    ?>
    <style>
    .acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .acv-code { font-size: 2em; font-weight: bold; color: #007cba; margin: 10px 0; }
    .acv-button { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .acv-button:hover { background: #005a87; }
    .acv-coupon.expired { border-color: #dc3232; opacity: 0.7; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-coupon .acv-button').on('click', function() {
            $(this).text('Copied! Redirecting...');
            navigator.clipboard.writeText($(this).siblings('.acv-code').text());
        });
    });
    </script>
    <?php
}

add_action('wp_head', 'acv_inline_assets');
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'discount' => '10%',
            'code' => '',
            'link' => '#',
            'expires' => '',
        ), $atts);

        $unique_code = $atts['code'] ?: 'SAVE' . wp_generate_password(4, false);
        $expires = $atts['expires'] ? '<p>Expires: ' . esc_html($atts['expires']) . '</p>' : '';

        return '<div class="affiliate-coupon-vault">
                    <h3>Exclusive Deal: ' . esc_html($atts['discount']) . ' Off!</h3>
                    <p>Use code: <strong>' . esc_html($unique_code) . '</strong></p>
                    <a href="' . esc_url($atts['link']) . '" class="coupon-btn" target="_blank">Shop Now & Save</a>
                    ' . $expires . '
                    <p>Your unique tracking ID: ' . get_current_user_id() . '</p>
                </div>';
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_vault_options'); ?>
                <?php do_settings_sections('affiliate_coupon_vault_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="affiliate_coupon_vault_settings[default_link]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_settings')['default_link'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Discount</th>
                        <td><input type="text" name="affiliate_coupon_vault_settings[default_discount]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_settings')['default_discount'] ?? '10%'); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon affiliate="amazon" discount="20%" link="https://aff.link" expires="2026-01-31"]</code></p>
            <p><strong>Pro Features (Upgrade for $49/year):</strong> Unlimited coupons, analytics dashboard, auto-expiry, email capture.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array());
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { background: #fffbd5; border: 2px dashed #f39c12; padding: 20px; border-radius: 10px; text-align: center; max-width: 400px; margin: 20px auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.affiliate-coupon-vault h3 { color: #e67e22; margin: 0 0 10px; }
.coupon-btn { background: #e67e22; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; transition: background 0.3s; }
.coupon-btn:hover { background: #d35400; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-btn').on('click', function() {
        gtag('event', 'coupon_click', { 'coupon_code': $(this).prev().find('strong').text() });
        // Pro: Track conversions
    });
});
</script>
<?php });
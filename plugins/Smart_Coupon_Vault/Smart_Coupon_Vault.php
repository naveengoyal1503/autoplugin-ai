/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon management for affiliate monetization. Generate, track, and display personalized coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartCouponVault {
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
        add_shortcode('scv_coupon_display', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-frontend', plugin_dir_url(__FILE__) . 'scv-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('scv-styles', plugin_dir_url(__FILE__) . 'scv-styles.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Coupon Vault', 'Coupon Vault', 'manage_options', 'scv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('scv_options', 'scv_settings');
        add_settings_section('scv_main', 'Coupon Settings', null, 'scv');
        add_settings_field('scv_api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'scv', 'scv_main');
        add_settings_field('scv_coupons', 'Coupons', array($this, 'coupons_field'), 'scv', 'scv_main');
    }

    public function api_key_field() {
        $options = get_option('scv_settings');
        echo '<input type="password" name="scv_settings[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter OpenAI API key for AI coupon generation (Pro feature).</p>';
    }

    public function coupons_field() {
        $options = get_option('scv_settings');
        $coupons = $options['coupons'] ?? array();
        echo '<textarea name="scv_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"code":"SAVE20","desc":"20% off","afflink":"https://aff.link","expiry":"2026-12-31"}</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('scv_options');
                do_settings_sections('scv');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, analytics, unlimited coupons for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $options = get_option('scv_settings');
        $coupons = json_decode($options['coupons'] ?? '[]', true);
        if (empty($coupons)) return '<p>No coupons configured.</p>';

        $coupon = $coupons[array_rand($coupons)];
        if (!$coupon) return '';

        ob_start();
        ?>
        <div class="scv-coupon-vault">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div class="scv-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?>" class="scv-button" target="_blank">Get Deal</a>
            <?php if (isset($coupon['expiry'])) echo '<small>Expires: ' . esc_html($coupon['expiry']) . '</small>'; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('scv_settings', array('coupons' => json_encode(array(
            array('code' => 'WELCOME10', 'desc' => '10% off first purchase', 'afflink' => '#', 'expiry' => '2026-06-30'),
            array('code' => 'SAVE20', 'desc' => '20% off software', 'afflink' => '#', 'expiry' => '2026-12-31')
        ))));
    }
}

SmartCouponVault::get_instance();

// Inline styles
add_action('wp_head', function() { ?>
<style>
.scv-coupon-vault { border: 2px solid #007cba; padding: 20px; border-radius: 8px; background: #f9f9f9; text-align: center; max-width: 300px; }
.scv-code { font-size: 2em; font-weight: bold; color: #007cba; margin: 10px 0; }
.scv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.scv-button:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.scv-coupon-vault .scv-code').click(function() {
        navigator.clipboard.writeText($(this).text());
        $(this).after('<span>Copied!</span>');
    });
});
</script>
<?php });
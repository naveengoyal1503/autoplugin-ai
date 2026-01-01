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
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
            'expires' => date('Y-m-d', strtotime('+30 days')),
        ), $atts);

        $unique_code = $atts['code'] ?: 'ACV' . wp_generate_uuid4() . substr(md5(auth()->user_id ?? ''), 0, 8);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px;">
            <h3 style="color: #007cba;">🎉 Exclusive Deal!</h3>
            <p><strong>Save <?php echo esc_html($atts['discount']); ?> OFF</strong></p>
            <div style="font-size: 24px; font-weight: bold; color: #e74c3c; margin: 10px 0;"><?php echo esc_html($unique_code); ?></div>
            <p>Expires: <?php echo esc_html($atts['expires']); ?></p>
            <a href="<?php echo esc_url($atts['link']); ?><?php echo strpos($atts['link'], '?') === false ? '?' : '&'; ?>ref=<?php echo esc_attr($unique_code); ?>&coupon=<?php echo esc_attr($unique_code); ?>" 
               class="coupon-btn" style="display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Get Deal Now (Affiliate Link)</a>
            <p style="font-size: 12px; margin-top: 10px;">Limited time offer - Generated exclusively for you!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate-coupon-vault');
        add_settings_field('sample_coupons', 'Sample Coupons', array($this, 'sample_coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function sample_coupons_field() {
        $settings = get_option('affiliate_coupon_vault_settings', array());
        echo '<textarea name="affiliate_coupon_vault_settings[sample_coupons]" rows="5" cols="50" class="large-text">' . esc_textarea($settings['sample_coupons'] ?? '[affiliate_coupon affiliate="amazon" discount="20%" link="https://amazon.com"]') . '</textarea>';
        echo '<p class="description">Add sample shortcodes here. Pro version unlocks unlimited custom coupons and analytics.</p>';
    }

    public function settings_page() {
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
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-top: 20px;">
                <h3>🚀 Go Pro for More Features!</h3>
                <ul>
                    <li>Unlimited coupons & affiliates</li>
                    <li>Click tracking & analytics</li>
                    <li>Custom branding & designs</li>
                    <li>API for brand partnerships</li>
                    <li>Email capture integration</li>
                </ul>
                <p><strong>Upgrade for $49/year → <a href="#" style="color: #007cba;">Get Pro Version</a></strong></p>
            </div>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array());
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info is-dismissible"><p><strong>Affiliate Coupon Vault:</strong> Unlock <strong>Pro features</strong> like unlimited coupons, analytics, and custom integrations for just <strong>$49/year</strong>! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade now →</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');

// Inline styles and scripts
add_action('wp_head', function() {
    echo '<style>.coupon-btn:hover { background: #005a87 !important; } .affiliate-coupon-vault { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }</style>';
});

// Dummy script
add_action('wp_footer', function() {
    if (is_admin()) return;
    echo '<script>console.log("Affiliate Coupon Vault loaded - Track your commissions!");</script>';
});
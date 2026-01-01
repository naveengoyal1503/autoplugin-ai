/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'amazon',
            'category' => 'electronics',
            'limit' => 5
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        $display_coupons = array_slice($coupons, 0, intval($atts['limit']));

        ob_start();
        ?>
        <div class="acv-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <h3>Exclusive Deals & Coupons</h3>
            <?php foreach ($display_coupons as $coupon): if (stripos($coupon['category'], $atts['category']) !== false): ?>
                <div class="acv-coupon">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                    <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
                    <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="acv-button" rel="nofollow">Shop Now & Save</a>
                    <small>Expires: <?php echo esc_html($coupon['expires']); ?></small>
                </div>
            <?php endif; endforeach; ?>
            <p><em>Pro: Unlock unlimited coupons & analytics</em></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Add Your Coupons', null, 'acv');
        add_settings_field('coupons', 'Coupons (JSON format: [{"title":"Deal","code":"SAVE10","discount":"10%","link":"https://aff.link","category":"electronics","expires":"2026-12-31"}])', array($this, 'coupons_field'), 'acv', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<textarea name="acv_coupons" rows="10" cols="80">' . esc_textarea(wp_json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Add affiliate coupons in JSON array. Free limits to 5.</p>';
    }

    public function admin_page() {
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
            <p><strong>Upgrade to Pro</strong> for unlimited coupons, auto-generation, analytics & more. <a href="#" onclick="alert('Pro upgrade link here')">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array(
                array('title' => 'Sample Deal', 'code' => 'SAVE20', 'discount' => '20% Off', 'link' => '#', 'category' => 'all', 'expires' => '2026-12-31')
            ));
        }
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlimited coupons & analytics. <a href="options-general.php?page=affiliate-coupon-vault">Upgrade now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Minimal CSS (inline for single file)
function acv_inline_styles() {
    echo '<style>
        .acv-vault { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-coupon { border-bottom: 1px solid #eee; padding: 15px 0; }
        .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .acv-button:hover { background: #005a87; }
    </style>';
}
add_action('wp_head', 'acv_inline_styles');
add_action('admin_head', 'acv_inline_styles');

// Minimal JS (inline)
function acv_inline_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-button").on("click", function() { gtag("event", "coupon_click", {"event_category": "affiliate"}); }); });</script>';
}
add_action('wp_footer', 'acv_inline_scripts');
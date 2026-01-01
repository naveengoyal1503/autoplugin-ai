/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Partner_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Partner Pro
 * Plugin URI: https://example.com/coupon-partner-pro
 * Description: Generate and manage exclusive custom coupons from affiliate partners to boost conversions and monetize your WordPress blog.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomCouponPartnerPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('cpp_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_register_style('cpp-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0');
            wp_enqueue_style('cpp-admin');
        }
    }

    public function admin_menu() {
        add_menu_page('Coupon Partner Pro', 'Coupons', 'manage_options', 'cpp-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save'])) {
            update_option('cpp_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('cpp_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Custom Coupon Partner Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" placeholder='[{"code":"SAVE20","desc":"20% off at Partner","link":"https://partner.com/?ref=yourid","affiliate":"Amazon"},{"code":"WELCOME10","desc":"$10 off first purchase","link":"https://partner2.com/?ref=yourid"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON format: [{&quot;code&quot;:&quot;CODE&quot;,&quot;desc&quot;:&quot;Description&quot;,&quot;link&quot;:&quot;Affiliate Link&quot;,&quot;affiliate&quot;:&quot;Network&quot;}]</p>
                <p><input type="submit" name="save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[cpp_coupon id=&quot;0&quot;]</code> (id is array index)</p>
            <?php if (get_option('cpp_premium') !== 'activated') { ?>
            <div class="notice notice-info"><p><strong>Go Premium:</strong> Unlimited coupons, analytics, auto-expiry. <a href="#">Upgrade Now</a></p></div>
            <?php } ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('cpp_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';
        $coupon = $coupons[$atts['id']];
        return '<div class="cpp-coupon"><h3>Exclusive: ' . esc_html($coupon['code']) . '</h3><p>' . esc_html($coupon['desc']) . '</p><a href="' . esc_url($coupon['link']) . '" class="button cpp-btn" target="_blank">Get Deal</a><small>via ' . esc_html($coupon['affiliate']) . '</small></div>';
    }

    public function enqueue_scripts() {
        wp_register_style('cpp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
        wp_enqueue_style('cpp-style');
    }

    public function activate() {
        if (!get_option('cpp_coupons')) update_option('cpp_coupons', '[]');
    }
}

new CustomCouponPartnerPro();

// Premium teaser
function cpp_premium_notice() {
    if (!get_option('cpp_premium') && current_user_can('manage_options')) {
        echo '<div class="notice notice-upgrade"><p>Unlock <strong>Custom Coupon Partner Pro Premium</strong>: Unlimited coupons, click tracking, expiry dates. <a href="https://example.com/premium">Get it now for $49/year!</a></p></div>';
    }
}
add_action('admin_notices', 'cpp_premium_notice');

// Inline styles
add_action('wp_head', function() {
    echo '<style>.cpp-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 8px; }.cpp-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; }.cpp-btn:hover { background: #005a87; }</style>';
});

?>
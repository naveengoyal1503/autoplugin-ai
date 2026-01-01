/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin.css');
        wp_register_script('ecp-admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('ecp_coupons', '');
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" placeholder="Code|Description|Affiliate Link|Expiry Date (YYYY-MM-DD)"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Format each line: CODE|Description|Affiliate URL|Expiry (or blank for no expiry)</p>
                <p><?php submit_button(); ?></p>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[exclusive_coupon code="YOURCODE"]</code> to display coupons anywhere.</p>
            <p><strong>Pro Features (Upgrade for $49/year):</strong> Unlimited coupons, click tracking, auto-expiry, analytics dashboard.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupons = explode("\n", get_option('ecp_coupons', ''));
        foreach ($coupons as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 3 && strtolower(trim($parts)) === strtolower($atts['code'])) {
                $expiry = isset($parts[3]) ? trim($parts[3]) : '';
                if ($expiry && strtotime($expiry) < current_time('timestamp')) {
                    return '<p class="ecp-expired">Coupon expired!</p>';
                }
                return '<div class="ecp-coupon"><strong>' . esc_html($parts) . '</strong><br>' . esc_html($parts[1]) . '<br><a href="' . esc_url($parts[2]) . '" target="_blank" class="button button-primary">Get Deal</a></div>';
            }
        }
        return '<p>Coupon not found.</p>';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "SAVE10|10% off first purchase|https://affiliate-link.com/?coupon=SAVE10|2026-12-31");
        }
    }
}

new ExclusiveCouponsPro();

// Enqueue styles for frontend
function ecp_styles() {
    wp_add_inline_style('dashicons', '.ecp-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }.ecp-expired { color: red; font-style: italic; }');
}
add_action('wp_enqueue_scripts', 'ecp_styles');

// Pro upsell notice
function ecp_upsell_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics & more for <a href="https://example.com/upgrade" target="_blank">$49/year</a>.</p></div> ';
}
add_action('admin_notices', 'ecp_upsell_notice');
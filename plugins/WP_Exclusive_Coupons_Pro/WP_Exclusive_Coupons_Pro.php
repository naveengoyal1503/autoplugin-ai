/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Exclusive_Coupons_Pro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('wpec_coupon_list', array($this, 'coupon_list_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpec-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('wpec_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', "Coupon Code|Brand Name|Discount %|Affiliate Link|Description");
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Coupons</h1>
            <form method="post">
                <p>Enter coupons (one per line): code|brand|discount|afflink|desc</p>
                <textarea name="coupons" rows="10" cols="80" style="width:100%;"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Coupons">
                </p>
            </form>
            <p>Shortcode: <code>[wpec_coupon_list]</code> | Pro: Unlimited coupons, analytics, auto-expiry.</p>
        </div>
        <?php
    }

    public function coupon_list_shortcode($atts) {
        $coupons_str = get_option('wpec_coupons', '');
        if (empty($coupons_str)) return '<p>No coupons available.</p>';

        $lines = explode("\n", trim($coupons_str));
        $output = '<div class="wpec-coupons">';
        foreach ($lines as $line) {
            $parts = explode('|', trim($line), 5);
            if (count($parts) >= 4) {
                $code = esc_html($parts);
                $brand = esc_html($parts[1]);
                $discount = esc_html($parts[2]);
                $link = esc_url($parts[3]);
                $desc = isset($parts[4]) ? esc_html($parts[4]) : '';
                $output .= '<div class="wpec-coupon">';
                $output .= '<h3>' . $brand . ' - ' . $discount . '</h3>';
                $output .= '<p>' . $desc . '</p>';
                $output .= '<div class="wpec-code">' . $code . '</div>';
                $output .= '<a href="' . $link . '" class="wpec-btn" target="_blank">Get Deal</a>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', "SAVE20|ExampleBrand|20% Off|https://example.com/aff?code=SAVE20|Exclusive deal for readers!");
        }
    }
}

new WP_Exclusive_Coupons_Pro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.wpec-coupons { max-width: 600px; margin: 20px 0; }
.wpec-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; background: #f9f9f9; }
.wpec-code { background: #fff; color: #d00; font-family: monospace; font-size: 24px; padding: 10px; text-align: center; margin: 10px 0; border: 2px dashed #d00; }
.wpec-btn { background: #0073aa; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; }
.wpec-btn:hover { background: #005a87; }
</style>
<?php });

// Pro upsell notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('WPEC_PRO')) {
        echo '<div class="notice notice-info"><p><strong>WP Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});
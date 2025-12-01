/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Optimizer.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Optimizer
 * Description: Automatically aggregate affiliate coupons, customize display, and optimize for revenue.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponOptimizer {
    private $option_name = 'aco_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('aco_coupons', array($this, 'display_coupons_shortcode'));
        add_action('init', array($this, 'fetch_coupons_cron'));
        add_action('aco_cron_hook', array($this, 'fetch_and_store_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        if (!wp_next_scheduled('aco_cron_hook')) {
            wp_schedule_event(time(), 'hourly', 'aco_cron_hook');
        }
        $this->fetch_and_store_coupons();
    }

    public function deactivate() {
        wp_clear_scheduled_hook('aco_cron_hook');
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupon Optimizer', 'Coupon Optimizer', 'manage_options', 'aco_settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['aco_api_key'])) {
            update_option('aco_api_key', sanitize_text_field($_POST['aco_api_key']));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $api_key = get_option('aco_api_key', '');

        echo '<h1>Affiliate Coupon Optimizer Settings</h1>';
        echo '<form method="POST">';
        echo '<label for="aco_api_key">Coupon API Key (Example: Affiliate Partner API): </label><br>';
        echo '<input type="text" name="aco_api_key" id="aco_api_key" value="' . esc_attr($api_key) . '" size="50"/><br><br>';
        echo '<input type="submit" value="Save Settings" class="button button-primary" />';
        echo '</form>';
    }

    // Simulate coupon fetch from an external affiliate API
    public function fetch_and_store_coupons() {
        $api_key = get_option('aco_api_key');
        if (empty($api_key)) return;

        // Example: Normally here would be a remote API request using $api_key to authenticate
        // Simulated coupons array
        $coupons = array(
            array('title' => '20% Off Tech Gear', 'code' => 'TECH20', 'link' => 'https://affiliate.example.com/tech?ref=yourid', 'expiry' => '2025-12-31'),
            array('title' => '15% Discount on Apparel', 'code' => 'APPAREL15', 'link' => 'https://affiliate.example.com/apparel?ref=yourid', 'expiry' => '2025-11-30'),
            array('title' => 'Free Shipping on Orders $50+', 'code' => 'FREESHIP', 'link' => 'https://affiliate.example.com/ship?ref=yourid', 'expiry' => '2026-01-15')
        );

        // Store coupons with timestamps
        update_option($this->option_name, $coupons);
    }

    public function display_coupons_shortcode() {
        $coupons = get_option($this->option_name);
        if (empty($coupons)) return '<p>No coupons available at the moment.</p>';

        $output = '<div class="aco-coupons-wrapper" style="border:1px solid #ddd; padding:15px; max-width: 400px;">';
        $output .= '<h3>Latest Affiliate Coupons</h3><ul style="list-style:none; padding-left:0;">';

        $today = date('Y-m-d');

        foreach ($coupons as $coupon) {
            if ($coupon['expiry'] < $today) continue; // Skip expired

            $output .= '<li style="margin-bottom:10px; border-bottom:1px dashed #ccc; padding-bottom:10px;">';
            $output .= '<strong>' . esc_html($coupon['title']) . '</strong><br>';
            $output .= 'Code: <code>' . esc_html($coupon['code']) . '</code><br>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" target="_blank" rel="nofollow noopener">Use Coupon</a><br>';
            $output .= '<small>Expires: ' . esc_html($coupon['expiry']) . '</small>';
            $output .= '</li>';
        }

        $output .= '</ul></div>';
        return $output;
    }

    public function fetch_coupons_cron() {
        // Cron schedules already added by WP defaults (hourly)
    }
}

new AffiliateCouponOptimizer();

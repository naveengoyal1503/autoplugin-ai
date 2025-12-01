/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: WP Paywall Pro
 * Description: Monetize your WordPress content with paywalls, subscriptions, and micropayments.
 * Version: 1.0
 * Author: WP Paywall Team
 */

if (!defined('ABSPATH')) {
    exit;
}

// Main plugin class
class WPPaywallPro {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'apply_paywall'));
        add_shortcode('paywall', array($this, 'paywall_shortcode'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Paywall Pro',
            'Paywall Pro',
            'manage_options',
            'wp-paywall-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Paywall Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp-paywall-pro-settings');
                do_settings_sections('wp-paywall-pro-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-paywall-pro', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('wp-paywall-pro', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0', true);
    }

    public function apply_paywall($content) {
        if (is_single() && get_post_type() === 'post') {
            $paywall_enabled = get_option('wp_paywall_enabled', false);
            if ($paywall_enabled) {
                $paywall_message = get_option('wp_paywall_message', 'This content is behind a paywall. Please subscribe or purchase access.');
                $content = '<div class="wp-paywall-message">' . esc_html($paywall_message) . '</div>';
            }
        }
        return $content;
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(array(
            'message' => 'This content is behind a paywall.',
        ), $atts, 'paywall');

        return '<div class="wp-paywall-shortcode">' . esc_html($atts['message']) . '</div>';
    }
}

// Initialize plugin
new WPPaywallPro();

// Register settings
add_action('admin_init', function() {
    register_setting('wp-paywall-pro-settings', 'wp_paywall_enabled');
    register_setting('wp-paywall-pro-settings', 'wp_paywall_message');
});

// Create plugin directories and files if they don't exist
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/wp-paywall-pro';
    if (!file_exists($plugin_dir)) {
        mkdir($plugin_dir, 0755, true);
    }
    if (!file_exists($plugin_dir . '/css')) {
        mkdir($plugin_dir . '/css', 0755);
    }
    if (!file_exists($plugin_dir . '/js')) {
        mkdir($plugin_dir . '/js', 0755);
    }
    file_put_contents($plugin_dir . '/css/style.css', "/* WP Paywall Pro CSS */
.wp-paywall-message, .wp-paywall-shortcode {
    background: #f9f9f9;
    padding: 20px;
    border: 1px solid #ddd;
    margin: 20px 0;
    text-align: center;
}");
    file_put_contents($plugin_dir . '/js/script.js', "// WP Paywall Pro JS
jQuery(document).ready(function($) {
    // Add your JS logic here
});");
});

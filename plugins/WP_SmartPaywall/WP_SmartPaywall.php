/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/
<?php
/**
 * Plugin Name: WP SmartPaywall
 * Plugin URI: https://example.com/wp-smartpaywall
 * Description: Monetize your content with flexible paywalls.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class WPSmartPaywall {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'apply_paywall'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function init() {
        register_setting('wp_smartpaywall', 'wp_smartpaywall_options');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-smartpaywall', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function apply_paywall($content) {
        if (is_admin() || !is_singular()) return $content;

        $options = get_option('wp_smartpaywall_options', array());
        $enabled = isset($options['enabled']) ? $options['enabled'] : false;
        if (!$enabled) return $content;

        $post_id = get_the_ID();
        $paywall_type = get_post_meta($post_id, '_wp_smartpaywall_type', true);
        if (!$paywall_type) return $content;

        if (is_user_logged_in() && current_user_can('manage_options')) return $content;

        $locked_content = '<div class="wp-smartpaywall">
            <p>This content is locked. Please subscribe or purchase access.</p>
            <a href="#" class="wp-smartpaywall-buy">Buy Access</a>
        </div>';

        return $locked_content;
    }

    public function admin_menu() {
        add_options_page(
            'WP SmartPaywall',
            'SmartPaywall',
            'manage_options',
            'wp-smartpaywall',
            array($this, 'admin_page')
        );
    }

    public function settings_init() {
        add_settings_section(
            'wp_smartpaywall_section',
            'Settings',
            null,
            'wp-smartpaywall'
        );

        add_settings_field(
            'enabled',
            'Enable Paywall',
            array($this, 'enabled_render'),
            'wp-smartpaywall',
            'wp_smartpaywall_section'
        );
    }

    public function enabled_render() {
        $options = get_option('wp_smartpaywall_options', array());
        $enabled = isset($options['enabled']) ? $options['enabled'] : false;
        echo '<input type="checkbox" name="wp_smartpaywall_options[enabled]" value="1" ' . checked(1, $enabled, false) . '>'; 
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP SmartPaywall</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_smartpaywall');
                do_settings_sections('wp-smartpaywall');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WPSmartPaywall;

// Add meta box for post paywall type
add_action('add_meta_boxes', function() {
    add_meta_box(
        'wp_smartpaywall_meta',
        'Paywall Settings',
        function($post) {
            $paywall_type = get_post_meta($post->ID, '_wp_smartpaywall_type', true);
            wp_nonce_field('wp_smartpaywall_nonce', 'wp_smartpaywall_nonce');
            echo '<label for="wp_smartpaywall_type">Paywall Type:</label>
                  <select name="wp_smartpaywall_type" id="wp_smartpaywall_type">
                      <option value="">None</option>
                      <option value="pay_per_view" ' . selected('pay_per_view', $paywall_type, false) . '>Pay Per View</option>
                      <option value="subscription" ' . selected('subscription', $paywall_type, false) . '>Subscription</option>
                      <option value="freemium" ' . selected('freemium', $paywall_type, false) . '>Freemium</option>
                  </select>';
        },
        'post'
    );
});

add_action('save_post', function($post_id) {
    if (!isset($_POST['wp_smartpaywall_nonce']) || !wp_verify_nonce($_POST['wp_smartpaywall_nonce'], 'wp_smartpaywall_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['wp_smartpaywall_type'])) {
        update_post_meta($post_id, '_wp_smartpaywall_type', sanitize_text_field($_POST['wp_smartpaywall_type']));
    }
});
?>
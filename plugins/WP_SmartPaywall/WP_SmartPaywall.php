/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/
<?php
/**
 * Plugin Name: WP SmartPaywall
 * Description: Monetize your content with flexible paywalls and recurring payments.
 * Version: 1.0
 * Author: WP Dev Team
 */

if (!defined('ABSPATH')) exit;

class WPSmartPaywall {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_wp_smartpaywall_unlock', array($this, 'unlock_content'));
        add_shortcode('smartpaywall', array($this, 'paywall_shortcode'));
    }

    public function init() {
        register_post_meta('', 'wp_smartpaywall_enabled', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
        ));
        register_post_meta('', 'wp_smartpaywall_price', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-smartpaywall-js', plugin_dir_url(__FILE__) . 'smartpaywall.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-smartpaywall-js', 'wpSmartPaywall', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_smartpaywall_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page(
            'WP SmartPaywall Settings',
            'SmartPaywall',
            'manage_options',
            'wp-smartpaywall',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>WP SmartPaywall Settings</h1>';
        echo '<p>Configure your paywall options here.</p>';
        echo '<form method="post" action="options.php">';
        settings_fields('wp_smartpaywall_options');
        do_settings_sections('wp-smartpaywall');
        submit_button();
        echo '</form></div>';
    }

    public function paywall_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'price' => 5,
            'type' => 'one-time', // one-time, subscription
        ), $atts);

        if (is_user_logged_in() && current_user_can('manage_options')) {
            return $content;
        }

        $user_paid = get_user_meta(get_current_user_id(), 'wp_smartpaywall_paid_' . get_the_ID(), true);
        if ($user_paid) {
            return $content;
        }

        return '<div class="wp-smartpaywall">
            <p>This content is locked. Pay $' . esc_attr($atts['price']) . ' to unlock.</p>
            <button class="wp-smartpaywall-pay" data-price="' . esc_attr($atts['price']) . '" data-type="' . esc_attr($atts['type']) . '" data-post-id="' . get_the_ID() . '">Pay Now</button>
        </div>';
    }

    public function unlock_content() {
        check_ajax_referer('wp_smartpaywall_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $price = floatval($_POST['price']);
        $type = sanitize_text_field($_POST['type']);

        // Simulate payment processing
        update_user_meta(get_current_user_id(), 'wp_smartpaywall_paid_' . $post_id, true);
        wp_send_json_success(array('message' => 'Content unlocked!'));
    }
}

new WPSmartPaywall;

// smartpaywall.js
// Place this in a separate file or inline
/*
jQuery(document).ready(function($) {
    $('.wp-smartpaywall-pay').on('click', function() {
        var price = $(this).data('price');
        var type = $(this).data('type');
        var postId = $(this).data('post-id');
        $.post(wpSmartPaywall.ajax_url, {
            action: 'wp_smartpaywall_unlock',
            nonce: wpSmartPaywall.nonce,
            post_id: postId,
            price: price,
            type: type
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
});
*/
?>
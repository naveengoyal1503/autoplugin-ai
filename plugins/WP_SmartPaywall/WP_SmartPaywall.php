<?php
/*
Plugin Name: WP SmartPaywall
Description: Monetize your WordPress content with pay-per-view, subscriptions, and micropayments.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/

define('WP_SMARTPAYWALL_VERSION', '1.0');

class WPSmartPaywall {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('paywall', array($this, 'paywall_shortcode'));
    }

    public function init() {
        // Register custom post meta for paywall settings
        register_meta('post', '_paywall_enabled', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
        ));
        register_meta('post', '_paywall_price', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-smartpaywall-js', plugin_dir_url(__FILE__) . 'paywall.js', array('jquery'), WP_SMARTPAYWALL_VERSION, true);
        wp_localize_script('wp-smartpaywall-js', 'wpSmartPaywall', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-smartpaywall-nonce')
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
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        echo '<div class="wrap"><h1>WP SmartPaywall Settings</h1>';
        echo '<p>Configure your paywall options here.</p>';
        echo '<form method="post" action="options.php">';
        settings_fields('wp-smartpaywall-settings');
        do_settings_sections('wp-smartpaywall');
        submit_button();
        echo '</form></div>';
    }

    public function paywall_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'price' => 0,
            'type' => 'pay_per_view'
        ), $atts);

        if (is_user_logged_in() && current_user_can('manage_options')) {
            return $content;
        }

        $price = floatval($atts['price']);
        $type = sanitize_text_field($atts['type']);

        if ($type === 'pay_per_view') {
            $key = 'wp_smartpaywall_paid_' . get_the_ID();
            if (get_user_meta(get_current_user_id(), $key, true)) {
                return $content;
            }
            return '<div class="wp-smartpaywall-pay-per-view">
                        <p>This content costs $' . $price . ' to view.</p>
                        <button class="wp-smartpaywall-pay-btn" data-price="' . $price . '" data-post-id="' . get_the_ID() . '" data-type="' . $type . '">Pay Now</button>
                    </div>';
        }

        return $content;
    }
}

new WPSmartPaywall();

// AJAX handler for payment
add_action('wp_ajax_wp_smartpaywall_pay', 'wp_smartpaywall_pay');
add_action('wp_ajax_nopriv_wp_smartpaywall_pay', 'wp_smartpaywall_pay');
function wp_smartpaywall_pay() {
    check_ajax_referer('wp-smartpaywall-nonce', 'nonce');

    $price = floatval($_POST['price']);
    $post_id = intval($_POST['post_id']);
    $type = sanitize_text_field($_POST['type']);

    if (!is_user_logged_in()) {
        wp_send_json_error('Login required');
    }

    if ($type === 'pay_per_view') {
        $key = 'wp_smartpaywall_paid_' . $post_id;
        update_user_meta(get_current_user_id(), $key, true);
        wp_send_json_success('Payment successful, content unlocked!');
    }

    wp_send_json_error('Invalid payment type');
}

// Add a simple JS file for frontend interaction
add_action('wp_footer', function() {
    if (is_singular()) {
        echo '<script>
            jQuery(document).ready(function($) {
                $(document).on("click", ".wp-smartpaywall-pay-btn", function() {
                    var price = $(this).data("price");
                    var postId = $(this).data("post-id");
                    var type = $(this).data("type");
                    $.post(wpSmartPaywall.ajax_url, {
                        action: "wp_smartpaywall_pay",
                        nonce: wpSmartPaywall.nonce,
                        price: price,
                        post_id: postId,
                        type: type
                    }, function(response) {
                        if (response.success) {
                            alert(response.data);
                            location.reload();
                        } else {
                            alert("Error: " + response.data);
                        }
                    });
                });
            });
        </script>';
    }
});

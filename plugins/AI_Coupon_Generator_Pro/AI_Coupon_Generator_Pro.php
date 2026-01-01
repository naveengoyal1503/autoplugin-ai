/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGeneratorPro {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function activate() {
        add_option('aicoupon_api_key', '');
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        wp_register_style('aicoupon-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('aicoupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicoupon-script', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicoupon_nonce')));
    }

    public function enqueue_scripts() {
        if (is_page() || is_single()) {
            wp_enqueue_style('aicoupon-style');
            wp_enqueue_script('aicoupon-script');
        }
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'aicoupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicoupon_api_key'])) {
            update_option('aicoupon_api_key', sanitize_text_field($_POST['aicoupon_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('aicoupon_api_key');
        echo '<div class="wrap"><h1>AI Coupon Generator Pro Settings</h1><form method="post"><table class="form-table"><tr><th>AI API Key (OpenAI)</th><td><input type="text" name="aicoupon_api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td></tr></table><p class="submit"><input type="submit" class="button-primary" value="Save"></p></form><p>Upgrade to Pro for unlimited generations and analytics.</p></div>';
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('aicoupon_nonce', 'nonce');
        $prompt = sanitize_text_field($_POST['prompt']);
        $api_key = get_option('aicoupon_api_key');

        if (!$api_key) {
            wp_die(json_encode(array('error' => 'API key not set')));
        }

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => 'Generate a unique discount coupon code for: ' . $prompt . ' Include affiliate tracking param ?ref=blogger')),
                'max_tokens' => 50,
            )),
        ));

        if (is_wp_error($response)) {
            wp_die(json_encode(array('error' => 'API error')));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $coupon = $body['choices']['message']['content'] ?? 'SAVE20';

        // Save coupon
        $coupons = get_option('aicoupon_coupons', array());
        $coupons[] = array('code' => $coupon, 'prompt' => $prompt, 'date' => current_time('mysql'));
        update_option('aicoupon_coupons', $coupons);

        wp_die(json_encode(array('coupon' => $coupon)));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('aicoupon_coupons', array());
        ob_start();
        echo '<div id="ai-coupon-container" class="ai-coupon-pro"><button id="generate-coupon" data-prompt="' . esc_attr($atts['id'] ? $coupons[$atts['id']]['prompt'] : 'general shopping') . '">Generate Fresh Coupon</button><div id="coupon-display"></div><p>Limited time deal! <strong>Track your savings with affiliate links.</strong></p></div>';
        return ob_get_clean();
    }
}

new AICouponGeneratorPro();

// Free version limits to 5 coupons
add_action('admin_notices', function() {
    $coupons = get_option('aicoupon_coupons', array());
    if (count($coupons) >= 5 && !get_option('aicoupon_pro')) {
        echo '<div class="notice notice-warning"><p>Upgrade to <strong>AI Coupon Pro</strong> for unlimited coupons! <a href="https://example.com/upgrade">Get Pro</a></p></div>';
    }
});
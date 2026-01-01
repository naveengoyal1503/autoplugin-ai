/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates unique affiliate coupons and personalized discount codes for WordPress blogs, boosting conversions through AI-powered promo creation and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-coupon
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateCouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_coupon_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function activate() {
        add_option('ai_coupon_limit', 5);
        add_option('ai_coupon_used', 0);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_id = get_option('ai_coupon_affiliate_id', '');
        $used = get_option('ai_coupon_used', 0);
        $limit = get_option('ai_coupon_limit', 5);
        echo '<div class="wrap"><h1>AI Affiliate Coupon Settings</h1><form method="post">';
        echo '<p><label>AI API Key (e.g., OpenAI): <input type="text" name="api_key" value="' . esc_attr($api_key) . '" size="50"></label></p>';
        echo '<p><label>Your Affiliate ID: <input type="text" name="affiliate_id" value="' . esc_attr($affiliate_id) . '" size="50"></label></p>';
        echo '<p>Free limit: ' . $limit . ' | Used: ' . $used . ' <a href="https://example.com/pro">Upgrade to Pro for unlimited</a></p>';
        echo '<p><input type="submit" name="submit" class="button-primary" value="Save Settings"></p></form></div>';
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for unlimited coupons and tracking! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }

    public function generate_coupon($product) {
        $api_key = get_option('ai_coupon_api_key');
        if (!$api_key) return false;

        $prompt = "Generate a unique 10% discount coupon code for: " . $product . " Affiliate ID: " . get_option('ai_coupon_affiliate_id');
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 50,
            )),
        ));

        if (is_wp_error($response)) return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = trim($body['choices']['message']['content'] ?? '');

        $used = get_option('ai_coupon_used', 0);
        $limit = get_option('ai_coupon_limit', 5);
        if ($used >= $limit && get_option('ai_coupon_pro') !== 'yes') {
            return 'PRO_UPGRADE';
        }
        update_option('ai_coupon_used', $used + 1);

        set_transient('ai_coupon_' . md5($code), $code, HOUR_IN_SECONDS);
        return $code;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('product' => 'Default Product'), $atts);
        $code = $this->generate_coupon($atts['product']);
        if ($code === 'PRO_UPGRADE') {
            return '<p><strong>Upgrade to Pro for more coupons!</strong> <a href="https://example.com/pro">Get Unlimited</a></p>';
        }
        if (!$code) {
            return '<p>Generating coupon...</p>';
        }
        return '<div style="background:#fff3cd;padding:20px;border:1px solid #ffeaa7;border-radius:5px;"><h3>Your Exclusive Coupon: <strong>' . esc_html($code) . '</strong></h3><p>Use this for 10% off ' . esc_html($atts['product']) . '! <a href="https://example.com/affiliate?code=' . urlencode($code) . '" target="_blank">Shop Now (Affiliate)</a></p><small>Generated by AI Affiliate Coupon Generator</small></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }
}

new AIAffiliateCouponGenerator();

// Pro unlock function (for license key)
function ai_coupon_activate_pro($license) {
    if (hash('sha256', $license) === 'pro_unlock_hash_here') { // Replace with real hash
        update_option('ai_coupon_pro', 'yes');
        update_option('ai_coupon_limit', 999);
    }
}

// CSS (inline for single file)
function ai_coupon_inline_css() {
    echo '<style>.ai-coupon-promo {background:#007cba;color:white;padding:10px;border-radius:3px;text-align:center;margin:10px 0;}</style>';
}
add_action('wp_head', 'ai_coupon_inline_css');
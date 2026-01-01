/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator for WordPress. Create unique coupons and affiliate deals effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGeneratorPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_generate_coupon', array($this, 'handle_coupon_generation'));
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Pro Settings',
            'AI Coupon Pro',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_pro_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_pro_key', '');
        echo '<div class="wrap"><h1>AI Coupon Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>OpenAI API Key (Pro)</th>
                    <td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text" placeholder="sk-..."><br>
                    <small>Enter your OpenAI API key for AI-powered coupon generation. Free version uses mock AI.</small></td>
                </tr>
            </table>
            ' . wp_nonce_field('ai_coupon_nonce') . '
            <p><input type="submit" name="submit" class="button-primary" value="Save Settings"></p>
        </form></div>';
    }

    public function generate_coupon($prompt = 'Generate a unique 10% off coupon code for fashion items') {
        $api_key = get_option('ai_coupon_pro_key');
        if ($api_key) {
            // Real OpenAI API call (Pro feature)
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
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                return trim($body['choices']['message']['content'] ?? 'AI-COUPON-10OFF');
            }
        }
        // Free mock AI
        $codes = array('SAVE10', 'DEAL20', 'COUPON15', 'FLASH10', 'BOGO50');
        return $codes[array_rand($codes)];
    }

    public function handle_coupon_generation() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
            wp_die('Unauthorized');
        }
        $coupon = $this->generate_coupon(sanitize_text_field($_POST['prompt']));
        wp_redirect(add_query_arg('generated_coupon', urlencode($coupon), wp_get_referer()));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'https://example.com/ref',
            'discount' => '10%',
            'expires' => date('Y-m-d', strtotime('+30 days')),
        ), $atts);

        $code = $this->generate_coupon("Generate unique coupon for {$atts['discount']} off");
        ob_start();
        echo '<div style="border:2px solid #28a745; padding:20px; text-align:center; background:#f8f9fa;">
            <h3>Exclusive Deal!</h3>
            <p>Use code: <strong>' . esc_html($code) . '</strong> for ' . esc_html($atts['discount']) . ' off!</p>
            <p>Expires: ' . esc_html($atts['expires']) . '</p>
            <a href="' . esc_url($atts['affiliate']) . '" target="_blank" class="button" style="background:#28a745;color:white;padding:10px 20px;text-decoration:none;">Shop Now & Save</a>
        </div>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGeneratorPro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_pro_key') && !is_admin()) {
        echo '<div style="position:fixed;bottom:20px;right:20px;background:#007cba;color:white;padding:15px;border-radius:5px;z-index:9999;">
            <strong>Upgrade to Pro</strong> for unlimited AI coupons & analytics! <a href="https://example.com/pro" style="color:#fff;">Get Pro ($49/yr)</a>
            <button onclick="this.parentElement.style.display=\'none\';">&times;</button>
        </div>';
    }
}
add_action('wp_footer', 'ai_coupon_pro_notice');

// Create style.css placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '/* AI Coupon Pro Styles */ .button:hover { opacity: 0.9; }');
}

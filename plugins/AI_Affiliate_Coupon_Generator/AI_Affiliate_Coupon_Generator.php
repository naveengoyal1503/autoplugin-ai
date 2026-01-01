/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-affiliate-coupon', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ai_coupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_link', esc_url_raw($_POST['affiliate_link']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_link = get_option('ai_coupon_affiliate_link', '');
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" value="<?php echo esc_attr($affiliate_link); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited generations, analytics, and more for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'popular product',
            'niche' => 'ecommerce'
        ), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-<?php echo uniqid(); ?>" class="ai-coupon-container">
            <div class="coupon-loading">Generating your exclusive coupon...</div>
            <div class="coupon-content" style="display:none;">
                <div class="coupon-code"></div>
                <div class="coupon-description"></div>
                <a class="coupon-button" href="#" target="_blank">Get Deal Now</a>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-coupon-<?php echo uniqid(); ?> .coupon-loading').trigger('click');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $product = sanitize_text_field($_POST['product']);
        $niche = sanitize_text_field($_POST['niche']);
        $api_key = get_option('ai_coupon_api_key');
        $affiliate_link = get_option('ai_coupon_affiliate_link');

        if (empty($api_key) || empty($affiliate_link)) {
            wp_send_json_error('Please configure API key and affiliate link in settings.');
        }

        $prompt = "Generate a unique 10-character coupon code like SAVE20 and a compelling description for a $niche $product. Make it sound exclusive and urgent. Format: CODE: [code]\nDESC: [description]";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 100,
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('API error');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices']['message']['content'] ?? '';

        preg_match('/CODE: ([A-Z0-9]{10})/', $content, $code_match);
        preg_match('/DESC: (.*)/s', $content, $desc_match);

        $code = $code_match[1] ?? 'SAVE20';
        $desc = trim($desc_match[1] ?? 'Exclusive deal just for you!');

        $full_link = add_query_arg('coupon', $code, $affiliate_link);

        wp_send_json_success(array(
            'code' => $code,
            'description' => $desc,
            'link' => $full_link
        ));
    }

    public function activate() {
        add_option('ai_coupon_limit', 5); // Free limit
    }
}

new AIAffiliateCouponGenerator();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (!get_option('ai_coupon_api_key')) {
        echo '<div class="notice notice-info"><p>Configure <strong>AI Affiliate Coupon Generator</strong> in Settings &gt; AI Coupons. <a href="' . admin_url('options-general.php?page=ai-coupon') . '">Setup Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// Minimal CSS
add_action('wp_head', function() {
    echo '<style>.ai-coupon-container { border: 2px dashed #007cba; padding: 20px; text-align: center; margin: 20px 0; background: #f9f9f9; }.coupon-code { font-size: 2em; font-weight: bold; color: #007cba; margin: 10px 0; }.coupon-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }</style>';
});

?>
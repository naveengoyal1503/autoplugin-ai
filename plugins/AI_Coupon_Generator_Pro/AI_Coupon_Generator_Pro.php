/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates and manages personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    private $api_key = '';
    private $openai_endpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->api_key = get_option('ai_coupon_api_key', '');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited generations, analytics, custom branding. <a href="#" onclick="alert('Upgrade to Pro for $49/year')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function generate_coupon($product, $affiliate_link = '') {
        if (empty($this->api_key)) return 'Please set OpenAI API key in settings.';

        $prompt = "Generate a compelling 20% off coupon code for: $product. Include discount details, expiry (7 days), and engaging copy for affiliate marketing. Format: CODE: DESC: EXPIRES:";

        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(array('role' => 'user', 'content' => $prompt)),
            'max_tokens' => 100
        );

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data)
        );

        $response = wp_remote_post($this->openai_endpoint, $args);
        if (is_wp_error($response)) return 'API Error: ' . $response->get_error_message();

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['choices']['message']['content'])) {
            return $result['choices']['message']['content'] . "\n<a href='$affiliate_link' target='_blank'>Shop Now & Save!</a>";
        }
        return 'Generation failed.';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('product' => 'Sample Product', 'link' => ''), $atts);
        return $this->generate_coupon($atts['product'], $atts['link']);
    }

    public function activate() {
        add_option('ai_coupon_api_key', '');
    }
}

new AICouponGenerator();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('ai_coupon_api_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>AI Coupon Generator: Enter your OpenAI API key in <a href="options-general.php?page=ai-coupon">Settings</a> to start generating coupons! Pro version available.</p></div>';
    }
});
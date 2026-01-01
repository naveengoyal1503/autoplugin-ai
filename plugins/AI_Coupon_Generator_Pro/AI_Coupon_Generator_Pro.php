/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    private $api_key;
    private $is_pro = false;

    public function __construct() {
        $this->api_key = get_option('ai_coupon_openai_key', '');
        $this->is_pro = get_option('ai_coupon_pro', false);
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (current_user_can('manage_options') && isset($_GET['ai_coupon_activate_pro'])) {
            update_option('ai_coupon_pro', true);
            wp_redirect(admin_url('admin.php?page=ai-coupon-settings'));
            exit;
        }
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_coupon_save'])) {
            update_option('ai_coupon_openai_key', sanitize_text_field($_POST['openai_key']));
            $this->api_key = sanitize_text_field($_POST['openai_key']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="openai_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php if (!$this->is_pro): ?>
                <p><a href="?page=ai-coupon-settings&ai_coupon_activate_pro=1" class="button button-primary">Activate Pro (Unlimited Coupons & Analytics)</a></p>
                <?php endif; ?>
                <p class="submit"><input type="submit" name="ai_coupon_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <p>Use shortcode: <code>[ai_coupon affiliate="amazon" product="shoes"]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'generic',
            'product' => 'product',
            'discount' => '20%',
        ), $atts);

        if (!$this->api_key || !$this->is_pro && (rand(0, 4) > 0)) {
            return $this->free_coupon_html($atts);
        }

        $prompt = "Generate a compelling coupon code for {$atts['product']} from {$atts['affiliate']}, offering {$atts['discount']} off. Include terms, expiry, and affiliate link placeholder. Make it realistic and urgent.";
        $coupon_data = $this->generate_coupon_ai($prompt);

        return $this->render_coupon($coupon_data, $atts);
    }

    private function generate_coupon_ai($prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 150,
            )),
        ));

        if (is_wp_error($response)) {
            return array('code' => 'SAVE20', 'desc' => '20% off!');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['choices']['message']['content'] ?? '';
        return array('code' => 'AI20', 'desc' => $text);
    }

    private function free_coupon_html($atts) {
        return '<div class="ai-coupon-free">' .
            '<h3>Grab ' . esc_html($atts['discount']) . ' Off ' . esc_html($atts['product']) . '!</h3>' .
            '<p>Use code: <strong>FREE' . rand(100,999) . '</strong></p>' .
            '<a href="#" class="button">Shop Now (Pro for Real Links)</a>' .
            '</div>';
    }

    private function render_coupon($data, $atts) {
        $aff_link = $this->is_pro ? 'https://affiliate.link/' . $atts['affiliate'] : '#';
        return '<div class="ai-coupon-pro" style="border:2px solid #28a745;padding:20px;background:#f8f9fa;border-radius:10px;">' .
            '<h3>🔥 Exclusive Deal: ' . esc_html($atts['discount']) . ' Off ' . esc_html($atts['product']) . '!</h3>' .
            '<p><strong>Code: ' . esc_html($data['code']) . '</strong></p>' .
            '<p>' . esc_html($data['desc']) . '</p>' .
            '<a href="' . esc_url($aff_link) . '" class="ai-coupon-btn" style="background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Redeem Now & Save</a>' .
            '</div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function activate() {
        update_option('ai_coupon_pro', false);
    }
}

new AICouponGenerator();

// Inline CSS for self-contained
add_action('wp_head', function() {
    echo '<style>.ai-coupon-pro {max-width:300px;margin:20px auto;text-align:center;font-family:sans-serif;}.ai-coupon-btn:hover {background:#218838;}</style>';
});

// Pro upsell widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('ai_coupon_upsell', 'Upgrade to AI Coupon Pro', function() {
        echo '<p>Unlock unlimited AI coupons & analytics for $49/year!</p>';
        echo '<a href="' . admin_url('options-general.php?page=ai-coupon-settings') . '" class="button button-primary">Upgrade Now</a>';
    });
});
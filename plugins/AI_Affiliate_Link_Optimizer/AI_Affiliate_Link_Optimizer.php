/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Link Optimizer
 * Plugin URI: https://example.com/ai-affiliate-optimizer
 * Description: Automatically generates and inserts optimized affiliate links into AI-generated content using semantic analysis for maximum conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateOptimizer {
    private $api_key;
    private $affiliate_links = [];

    public function __construct() {
        $this->api_key = get_option('aio_api_key', '');
        $this->affiliate_links = get_option('aio_affiliate_links', []);
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('wp_ajax_aio_generate_links', [$this, 'ajax_generate_links']);
        add_filter('the_content', [$this, 'insert_affiliate_links']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        if (defined('AIO_PRO_VERSION')) {
            return;
        }
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_aio-settings') {
            return;
        }
        wp_enqueue_script('aio-admin', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aio-admin', 'aio_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aio_nonce')
        ]);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aio-front', plugin_dir_url(__FILE__) . 'front.js', ['jquery'], '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'AI Affiliate Optimizer Settings',
            'AI Affiliate Optimizer',
            'manage_options',
            'aio-settings',
            [$this, 'settings_page']
        );
    }

    public function settings_page() {
        if (isset($_POST['aio_save'])) {
            update_option('aio_api_key', sanitize_text_field($_POST['api_key']));
            update_option('aio_affiliate_links', array_map('sanitize_text_field', $_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('aio_api_key', '');
        $links = get_option('aio_affiliate_links', []);
        ?>
        <div class="wrap">
            <h1>AI Affiliate Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON: {"keyword":"link"})</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(json_encode($links)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'aio_save'); ?>
            </form>
            <h2>Test Link Generation</h2>
            <textarea id="aio-test-content" rows="5" cols="50" class="large-text">Enter sample content here...</textarea>
            <br>
            <button type="button" id="aio-generate" class="button button-primary">Generate Links</button>
            <div id="aio-result"></div>
        </div>
        <?php
    }

    public function ajax_generate_links() {
        check_ajax_referer('aio_nonce', 'nonce');
        if (!$this->api_key || empty($this->affiliate_links)) {
            wp_die('API key or links missing');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $prompt = "Analyze this content and suggest 3 places to insert affiliate links from: " . json_encode($this->affiliate_links) . " Output JSON: {\"suggestions\":[\"keyword1 (position): link1\", ...]}";
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 500
            ])
        ]);
        if (is_wp_error($response)) {
            wp_send_json_error('API error');
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_success($body['choices']['message']['content']);
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_links) || !is_singular()) {
            return $content;
        }
        // Simple keyword-based insertion for free version (Pro uses AI)
        foreach ($this->affiliate_links as $keyword => $link) {
            $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $replacement, $content, 1);
        }
        return $content;
    }

    public function activate() {
        add_option('aio_link_limit', 5);
    }

    public function deactivate() {}
}

new AIAffiliateOptimizer();

// Pro upsell notice
function aio_upsell_notice() {
    if (!defined('AIO_PRO_VERSION') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Affiliate Optimizer Pro</strong> for unlimited AI-powered link insertion and analytics! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'aio_upsell_notice');
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for SEO, readability, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private $api_key;

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('ai_optimizer_api_key');
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'settings_page'));
        add_action('wp_ajax_ai_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function activate() {
        add_option('ai_optimizer_enabled', 'yes');
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<p><button id="ai-optimize-btn" class="button button-primary" data-post-id="' . $post->ID . '">Optimize with AI</button></p>';
        if ($optimized) {
            echo '<p><strong>Status:</strong> Optimized on ' . date('Y-m-d H:i', $optimized) . '</p>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
    }

    public function settings_page() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-optimizer',
            array($this, 'settings_html')
        );
    }

    public function settings_html() {
        if (isset($_POST['submit'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>.</p>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_ajax_nonce')
        ));
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        $content = $post->post_content;

        if (strlen($content) > 5000) {
            wp_send_json_error('Content too long. Limit: 5000 characters.');
        }

        if (!$this->api_key) {
            wp_send_json_error('OpenAI API key not set. Go to Settings > AI Optimizer.');
        }

        $prompt = "Optimize this content for SEO, readability, and engagement. Improve structure, add keywords, make it concise and compelling. Original topic: " . $post->post_title . "\n\nContent: " . $content;

        $response = $this->call_openai($prompt);

        if (isset($response['choices']['message']['content'])) {
            update_post_meta($post_id, '_ai_optimized_content', $response['choices']['message']['content']);
            update_post_meta($post_id, '_ai_optimized', time());
            wp_send_json_success(array('content' => $response['choices']['message']['content']));
        } else {
            wp_send_json_error('AI optimization failed.');
        }
    }

    private function call_openai($prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 2000,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}

new AIContentOptimizer();

// Freemium notice
function ai_optimizer_freemium_notice() {
    if (!get_option('ai_optimizer_api_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Enter your <a href="' . admin_url('options-general.php?page=ai-optimizer') . '">OpenAI API key</a> to unlock AI optimization. <strong>Pro: Unlimited optimizations for $49/year</strong>.</p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_freemium_notice');
?>
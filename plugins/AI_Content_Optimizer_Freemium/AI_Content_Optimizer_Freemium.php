/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content using AI for better SEO and engagement. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private $api_key = '';
    private $usage_count = 0;
    private $is_premium = false;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('wp_ajax_upgrade_to_premium', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_premium = get_option('aco_premium_active', false);
        $this->usage_count = get_option('aco_usage_count', 0);
        $this->api_key = get_option('aco_api_key', '');
        if (empty($this->api_key)) {
            add_action('admin_notices', array($this, 'api_key_notice'));
        }
    }

    public function activate() {
        add_option('aco_usage_count', 0);
        add_option('aco_premium_active', false);
    }

    public function deactivate() {
        delete_option('aco_usage_count');
        delete_option('aco_premium_active');
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
        add_meta_box('ai-optimizer', 'Optimize with AI', array($this, 'content_optimizer_meta_box'), 'post', 'side');
    }

    public function settings_page() {
        if (isset($_POST['aco_api_key'])) {
            update_option('aco_api_key', sanitize_text_field($_POST['aco_api_key']));
            echo '<div class="notice notice-success"><p>API Key saved!</p></div>';
        }
        if (isset($_POST['aco_activate_premium'])) {
            // Simulate premium activation (in real: verify license via server)
            update_option('aco_premium_active', true);
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated! <a href="https://example.com/premium-checkout" target="_blank">Manage Subscription</a></p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="aco_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$this->is_premium) : ?>
            <h2>Upgrade to Premium</h2>
            <p>Free: 5 optimizations/month. Premium: Unlimited + advanced features for $9.99/mo.</p>
            <form method="post">
                <input type="submit" name="aco_activate_premium" value="Activate Premium (Demo)" class="button button-primary">
            </form>
            <?php else : ?>
            <p>Premium active! Usage: <?php echo $this->usage_count; ?>/Unlimited</p>
            <?php endif; ?>
            <p><strong>Usage:</strong> Edit a post, use the sidebar optimizer.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aco-optimize-btn').click(function() {
                var content = $('#content').val();
                $.post(ajaxurl, {action: 'optimize_content', content: content, nonce: '<?php echo wp_create_nonce('aco_nonce'); ?>'}, function(response) {
                    $('#content').val(response.optimized);
                });
            });
        });
        </script>
        <?php
    }

    public function content_optimizer_meta_box() {
        echo '<p><strong>Free uses left: ' . (5 - $this->usage_count) . '</strong></p>';
        if ($this->usage_count < 5 || $this->is_premium) {
            echo '<button type="button" id="aco-optimize-btn" class="button button-secondary">Optimize Content with AI</button>';
        } else {
            echo '<p><a href="/wp-admin/options-general.php?page=ai-content-optimizer" class="button">Upgrade to Premium</a></p>';
        }
        wp_nonce_field('aco_nonce');
    }

    public function handle_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium && $this->usage_count >= 5) {
            wp_die('Upgrade to premium for more uses.');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $prompt = "Optimize this content for SEO, readability, and engagement. Keep length similar: " . $content;
        $optimized = $this->call_openai($prompt);
        if (!$this->is_premium) {
            $this->usage_count++;
            update_option('aco_usage_count', $this->usage_count);
        }
        wp_send_json_success(array('optimized' => $optimized));
    }

    private function call_openai($prompt) {
        if (empty($this->api_key)) return $prompt . ' (Enter API key in settings)';
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 2000,
            )),
        ));
        if (is_wp_error($response)) return $prompt;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices']['message']['content'] ?? $prompt;
    }

    public function api_key_notice() {
        echo '<div class="notice notice-warning"><p>Enter your OpenAI API key in <a href="/wp-admin/options-general.php?page=ai-content-optimizer">Settings > AI Optimizer</a>.</p></div>';
    }
}

new AIContentOptimizer();

// Enqueue scripts for editor
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_script('jquery');
    }
});
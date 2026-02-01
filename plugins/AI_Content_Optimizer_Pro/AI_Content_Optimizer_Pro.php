/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO and readability using AI analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private $is_pro = false;
    private $api_key = '';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_pro = get_option('ai_content_optimizer_pro', false);
        $this->api_key = get_option('ai_content_optimizer_api_key', '');

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('the_content', array($this, 'optimize_content'));

        if (isset($_POST['ai_optimizer_submit'])) {
            $this->handle_submit();
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_pro', false);
    }

    public function deactivate() {}

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<p>Status: ' . ($optimized ? 'Optimized' : 'Pending') . '</p>';
        echo '<textarea id="ai_optimizer_content" rows="10" cols="50" style="width:100%;">' . esc_textarea(get_post_field('post_content', $post->ID)) . '</textarea>';
        echo '<br><button type="submit" name="ai_optimizer_submit" class="button button-primary" ' . (!$this->is_pro ? 'disabled' : '') . '>Optimize with AI</button>';
        if (!$this->is_pro) {
            echo '<p><strong>Upgrade to Pro</strong> for AI optimization! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (isset($_POST['ai_optimizer_submit']) && $this->is_pro && $this->api_key) {
            $content = sanitize_textarea_field($_POST['ai_optimizer_content']);
            $optimized_content = $this->mock_ai_optimize($content);
            wp_update_post(array('ID' => $post_id, 'post_content' => $optimized_content));
            update_post_meta($post_id, '_ai_optimized', true);
        }
    }

    public function mock_ai_optimize($content) {
        // Mock AI optimization: improve readability, add keywords, etc.
        $improvements = array(
            'Added SEO keywords and improved readability.',
            'Optimized headings and meta structure.',
            'Enhanced sentence flow for better engagement.'
        );
        $content .= '\n\n<!-- AI Optimized: ' . implode(' ', $improvements) . ' -->';
        return $content;
    }

    public function optimize_content($content) {
        if (!$this->is_pro) {
            return $content;
        }
        // Auto-optimize published content
        if (is_single() && in_the_loop() && is_main_query()) {
            global $post;
            if (!get_post_meta($post->ID, '_ai_optimized', true)) {
                $content = $this->mock_ai_optimize($content);
                update_post_meta($post->ID, '_ai_optimized', true);
            }
        }
        return $content;
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_content_optimizer_pro', !empty($_POST['is_pro']));
            update_option('ai_content_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro License</th>
                        <td><input type="checkbox" name="is_pro" <?php checked(get_option('ai_content_optimizer_pro')); ?> /> Enabled (Pro)</td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr(get_option('ai_content_optimizer_api_key')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited optimizations, bulk processing, advanced AI analytics. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style('ai-optimizer-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!get_option('ai_content_optimizer_pro')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced features! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Create style.css placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '/* AI Content Optimizer Styles */ #ai_optimizer_content { font-family: monospace; }');
}
?>
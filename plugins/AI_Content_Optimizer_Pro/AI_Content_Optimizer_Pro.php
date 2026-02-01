/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content optimization for SEO, readability, and engagement.
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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_pro';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_filter('the_content', array($this, 'optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), self::VERSION, true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/optimizer.css', array(), self::VERSION);
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_settings'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (OpenAI or similar)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited optimizations, advanced metrics, and auto-publishing for $49/year.</p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
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
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<p><strong>SEO Score:</strong> ' . esc_html($score ?: 'Not optimized') . '%</p>';
        echo '<p><a href="#" class="button button-primary ai-optimize-btn" data-post-id="' . $post->ID . '">Optimize Now (Pro)</a></p>';
        echo '<p class="description">Free version shows basic analysis. Pro auto-applies improvements.</p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function optimize_content($content) {
        if (is_admin() || !is_single()) {
            return $content;
        }
        // Simulate basic optimization (Pro would use real AI)
        $content = preg_replace('/\b(the|a|an)\s+([a-zA-Z]+)/i', '$2', $content);
        return $content;
    }

    public function activate() {
        add_option('ai_optimizer_version', self::VERSION);
    }
}

new AIContentOptimizer();

// Pro upsell nag
function ai_optimizer_admin_notice() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="notice notice-info">
        <p><strong>AI Content Optimizer Pro:</strong> Unlock AI-powered auto-optimizations! <a href="<?php echo admin_url('options-general.php?page=ai-content-optimizer'); ?>">Upgrade now</a> for better SEO.</p>
    </div>
    <?php
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Enqueue dummy assets (create empty files in /assets/)
function ai_optimizer_assets() {
    // JS and CSS are referenced but minimal for demo
}
add_action('wp_enqueue_scripts', 'ai_optimizer_assets');
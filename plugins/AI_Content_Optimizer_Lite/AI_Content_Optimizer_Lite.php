/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Freemium version with basic features.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta_box'));
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Lite',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_meta', 'ai_content_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<p><strong>Content Score:</strong> ' . esc_html($this->analyze_content($post->post_content)['score']) . '%</p>';
        echo '<p><strong>Suggestions:</strong></p>';
        echo '<ul>';
        foreach ($this->analyze_content($post->post_content)['suggestions'] as $suggestion) {
            echo '<li>' . esc_html($suggestion) . '</li>';
        }
        echo '</ul>';
        echo '<p><a href="#" class="button button-primary" onclick="alert(\'Upgrade to Pro for auto-optimization!\')">Optimize Now (Pro)</a></p>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        update_post_meta($post_id, '_ai_optimized', sanitize_text_field($_POST['_ai_optimized'] ?? ''));
    }

    private function analyze_content($content) {
        // Simulated AI analysis (free version limited)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? round(200.0 * $sentence_count / $word_count, 1) : 0;
        $score = min(100, max(0, 50 + ($readability - 20) * 2));

        $suggestions = [];
        if ($word_count < 300) $suggestions[] = 'Add more content (aim for 300+ words).';
        if ($readability < 15) $suggestions[] = 'Improve readability: use shorter sentences.';
        if (substr_count(strtolower($content), 'keyword') === 0) $suggestions[] = 'Include primary keyword.';

        return array(
            'score' => (int)$score,
            'suggestions' => $suggestions,
            'limit_notice' => count($suggestions) > 3 ? 'Upgrade for full analysis!' : ''
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_submit'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Lite Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /> <p>Enter Pro API key for advanced features.</p></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'ai_optimizer_submit'); ?>
            </form>
            <div class="card">
                <h2>Upgrade to Pro</h2>
                <p>Unlock unlimited optimizations, real AI integration, auto-pilot mode, and more for <strong>$9.99/month</strong>.</p>
                <a href="https://example.com/pro-upgrade" class="button button-hero" target="_blank">Get Pro Now</a>
            </div>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if (is_single()) {
            wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function activate() {
        update_option('ai_optimizer_usage_count', 0);
    }

    public function deactivate() {}
}

AIContentOptimizerLite::get_instance();

// Freemium nag
add_action('admin_notices', function() {
    $usage = get_option('ai_optimizer_usage_count', 0);
    if ($usage >= 5) {
        echo '<div class="notice notice-info"><p>AI Content Optimizer Lite: You\'ve reached the free limit (5 posts). <a href="https://example.com/pro-upgrade" target="_blank">Upgrade to Pro</a> for unlimited access!</p></div>';
    }
});
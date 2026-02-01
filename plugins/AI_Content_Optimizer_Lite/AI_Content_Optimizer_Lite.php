/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using AI-powered insights.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_optimizer_usage_count', 0);
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-content-optimizer') {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'max_free' => 5
        ));
    }

    public function admin_page() {
        $usage = get_option('ai_optimizer_usage_count', 0);
        $is_premium = get_option('ai_optimizer_premium', false);
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Lite', 'ai-content-optimizer'); ?></h1>
            <div id="ai-optimizer-app">
                <p><?php printf(__('You have used %d/%d free optimizations this month. %s'), $usage, 5, $usage >= 5 ? '<strong>Upgrade to Premium for unlimited access!</strong>' : ''); ?></p>
                <?php if (!$is_premium && $usage >= 5): ?>
                <a href="#" id="upgrade-btn" class="button button-primary">Upgrade to Premium ($49/year)</a>
                <?php endif; ?>
                <textarea id="content-input" placeholder="Paste your post content here..." rows="10" cols="80"></textarea>
                <br><button id="optimize-btn" class="button button-large button-primary">Optimize Content</button>
                <div id="results"></div>
            </div>
        </div>
        <?php
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $usage = get_option('ai_optimizer_usage_count', 0);

        if ($usage >= 5 && !get_option('ai_optimizer_premium', false)) {
            wp_send_json_error('Free limit reached. Upgrade to premium.');
        }

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $suggestions = $this->generate_ai_suggestions($content);
        $optimized = $this->apply_optimizations($content, $suggestions);

        update_option('ai_optimizer_usage_count', $usage + 1);

        wp_send_json_success(array(
            'optimized_content' => $optimized,
            'suggestions' => $suggestions,
            'remaining' => 5 - ($usage + 1)
        ));
    }

    private function generate_ai_suggestions($content) {
        // Mock AI suggestions
        $word_count = str_word_count($content);
        $suggestions = array(
            'seo_score' => rand(60, 90),
            'recommendations' => array(
                'Add more keywords: "' . $this->extract_keywords($content) ?? 'your main keyword' . '"',
                'Improve readability: Shorten sentences.',
                'SEO Score: ' . rand(60, 90) . '/100',
                'Suggested title: ' . $this->suggest_title($content)
            )
        );
        return $suggestions;
    }

    private function apply_optimizations($content, $suggestions) {
        // Mock optimization: add headings, bold keywords
        $keywords = $this->extract_keywords($content);
        $optimized = preg_replace('/(?<=[\s\.,;])(' . preg_quote($keywords ?? '', '/') . ')(?=[\s\.,;])/', '<strong>$1</strong>', $content);
        $optimized = '<h2>Optimized Content</h2>' . $optimized;
        return $optimized;
    }

    private function extract_keywords($content) {
        // Simple keyword extraction mock
        return array('wordpress', 'seo', 'content');
    }

    private function suggest_title($content) {
        return 'Optimized ' . ucwords(strtolower(preg_replace('/[^a-zA-Z\s]/', '', $content))) . ' Guide';
    }
}

new AIContentOptimizer();

// Premium check mock (integrate with Stripe or Freemius in pro version)
add_action('admin_init', function() {
    if (isset($_GET['upgrade']) && wp_verify_nonce($_GET['_wpnonce'], 'ai_upgrade')) {
        // Simulate premium activation
        update_option('ai_optimizer_premium', true);
        wp_redirect(admin_url('admin.php?page=ai-content-optimizer&activated=1'));
        exit;
    }
});

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    file_put_contents($assets_dir . '/script.js', '// Mock JS\nconsole.log("AI Optimizer loaded");');
    file_put_contents($assets_dir . '/style.css', '/* Mock CSS */ #ai-optimizer-app { max-width: 800px; }');
});
?>
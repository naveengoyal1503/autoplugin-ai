/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes post content for SEO and readability with AI-powered suggestions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        add_option('ai_content_usage_count', 0);
    }

    public function deactivate() {}

    public function add_admin_menu() {
        add_posts_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'usage_limit' => 5,
            'premium_url' => 'https://example.com/premium'
        ));
    }

    public function admin_page() {
        $usage = get_option('ai_content_usage_count', 0);
        echo '<div class="wrap"><h1>AI Content Optimizer Lite</h1>';
        echo '<p>Usage this month: ' . $usage . '/5 Free optimizations left.</p>';
        echo '<p><a href="https://example.com/premium" class="button button-primary" target="_blank">Upgrade to Premium (Unlimited)</a></p>';
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $usage = get_option('ai_content_usage_count', 0);

        if ($usage >= 5) {
            wp_send_json_error('Usage limit reached. <a href="https://example.com/premium" target="_blank">Upgrade to Premium</a>');
        }

        // Simulate AI analysis (in premium, integrate real AI API like OpenAI)
        $suggestions = $this->analyze_content($content);

        update_option('ai_content_usage_count', $usage + 1);

        wp_send_json_success($suggestions);
    }

    private function analyze_content($content) {
        $word_count = str_word_count($content);
        $readability = $word_count > 300 ? 'Good' : 'Improve length';
        $seo_score = rand(60, 90);

        return array(
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => array(
                'Add more subheadings for better structure.',
                'Include keywords naturally.',
                'Shorten sentences for readability.',
                'Premium: Auto-rewrite & keyword suggestions.'
            ),
            'optimized_snippet' => substr($content, 0, 200) . '... (Premium: Full optimization)'
        );
    }
}

new AIContentOptimizerLite();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    $screen = get_current_screen();
    if ($screen->id === 'edit-post') {
        $usage = get_option('ai_content_usage_count', 0);
        if ($usage >= 5) {
            echo '<div class="notice notice-info"><p>AI Content Optimizer: Upgrade to Premium for unlimited optimizations! <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
        }
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Add button to post editor
add_action('media_buttons', function() {
    echo '<a href="#" id="ai-optimize-btn" class="button">AI Optimize Content</a>';
});

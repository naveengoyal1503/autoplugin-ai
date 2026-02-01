/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        $score = get_post_meta($post->ID, '_ai_score', true);
        echo '<div id="ai-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="analyze-btn" class="button">Analyze Free</button>';
        echo '<div id="ai-suggestions"></div>';
        echo '<p><small>Upgrade to Pro for advanced AI suggestions and bulk tools. <a href="https://example.com/pricing" target="_blank">Get Pro</a></small></p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce_field']) || !wp_verify_nonce($_POST['ai_optimizer_nonce_field'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate basic analysis (free version)
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count > 500 ? 20 : 0) + (substr_count($content, '<h2') > 1 ? 15 : 0) + (substr_count(strtolower($content), 'keyword') > 2 ? 15 : 0));
        update_post_meta($post_id, '_ai_score', $score);

        // Free suggestion
        $suggestions = array(
            'Free: Add more subheadings for better readability.',
            'Pro: Unlock AI keyword suggestions and full rewrite. Upgrade now!'
        );

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Freemius integration stub for premium (replace with real Freemius key)
// require_once dirname(__FILE__) . '/freemius/start.php';

?>
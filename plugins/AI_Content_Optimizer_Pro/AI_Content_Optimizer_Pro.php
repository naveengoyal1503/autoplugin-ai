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
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_pro_activated', '1');
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
            wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_meta_nonce', 'ai_content_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        $pro = get_option('ai_content_pro_activated', '0');
        echo '<div id="ai-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="analyze-content" class="button">Analyze Content</button>';
        if ($pro === '1') {
            echo '<button id="optimize-content" class="button button-primary" style="margin-top:10px;">Optimize (Pro)</button>';
            echo '<div id="optimized-content" style="display:none; margin-top:10px;"></div>';
        } else {
            echo '<p><em>Upgrade to Pro for auto-optimizations!</em></p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_nonce']) || !wp_verify_nonce($_POST['ai_content_nonce'], 'ai_content_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['_ai_content_score'])) {
            update_post_meta($post_id, '_ai_content_score', sanitize_text_field($_POST['_ai_content_score']));
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        $post_id = intval($_POST['post_id']);
        $action = sanitize_text_field($_POST['action_type']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $readability = 100 - (60 / max(1, $word_count / count($sentences)) * 10); // Flesch-like
        $score = round(min(100, max(0, 50 + ($readability / 2))));

        if ($action === 'analyze') {
            wp_send_json_success(array('score' => $score));
        } elseif ($action === 'optimize' && get_option('ai_content_pro_activated', '0') === '1') {
            // Pro: Simulate optimization
            $optimized = $this->optimize_content($content);
            wp_send_json_success(array('content' => $optimized));
        } else {
            wp_send_json_error('Pro feature required');
        }
    }

    private function optimize_content($content) {
        // Simple optimizations: add headings, improve structure
        $content = preg_replace('/\n\n+/', "\n\n", $content);
        $content = str_replace('<p>', '<p class="optimized">', $content);
        return $content . '\n\n<h3>Optimized by AI Content Optimizer Pro</h3>';
    }
}

new AIContentOptimizer();

// Freemium notice
add_action('admin_notices', function() {
    if (get_option('ai_content_pro_activated', '0') === '0') {
        echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations with <strong>AI Content Optimizer Pro</strong> for $49/year!</p></div>';
    }
});
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
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_shortcode('ai_content_score', array($this, 'content_score_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_pro', 'free');
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
        wp_nonce_field('ai_content_nonce', 'ai_content_nonce');
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        echo '<div id="ai-content-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '</strong></div>';
        echo '<button id="analyze-content" class="button button-primary">Analyze Content</button>';
        echo '<button id="optimize-content" class="button button-secondary" style="display:none;">Optimize with AI</button>';
        echo '<div id="ai-suggestions"></div>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_content_nonce']) || !wp_verify_nonce($_POST['ai_content_nonce'], 'ai_content_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_content_nonce')
        ));
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_content_nonce', 'nonce');
        if (get_option('ai_content_optimizer_pro') !== 'pro') {
            wp_send_json_error('Upgrade to Pro for AI optimization.');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Simulate AI optimization (in real version, integrate OpenAI API)
        $optimized = $this->mock_ai_optimize($content);
        wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        $score = $this->calculate_score($optimized);
        update_post_meta($post_id, '_ai_content_score', $score);
        wp_send_json_success(array('score' => $score, 'content' => $optimized));
    }

    private function mock_ai_optimize($content) {
        // Mock AI: Add headings, improve readability
        $optimized = preg_replace('/<p>/', '<h3>', $content, 1);
        $optimized .= '<p>Optimized by AI Content Optimizer Pro!</p>';
        return $optimized;
    }

    private function calculate_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $readability = $word_count > 300 ? 85 : 50;
        return min(100, $readability + rand(0, 15));
    }

    public function content_score_shortcode($atts) {
        $post_id = get_the_ID();
        $score = get_post_meta($post_id, '_ai_content_score', true);
        return '<span class="ai-score" style="background:#0073aa;color:white;padding:5px;border-radius:3px;">AI Score: ' . esc_html($score ?: 'N/A') . '%</span>';
    }
}

new AIContentOptimizer();

// Freemium check
function ai_content_optimizer_pro_notice() {
    if (get_option('ai_content_optimizer_pro') !== 'pro' && current_user_can('administrator')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Content Optimizer Pro</strong> for unlimited AI optimizations! <a href="https://example.com/upgrade">Get Pro Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_content_optimizer_pro_notice');

// Enqueue dummy JS
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('jquery', 'jQuery(document).ready(function($){ $("#analyze-content").click(function(){ $("#ai-content-score").html("Score: <strong>Analyzing...</strong>"); $.post(ai_ajax.ajax_url, {action:"optimize_content", nonce:ai_ajax.nonce, post_id: $("#post_ID").val() }, function(res){ if(res.success){ $("#ai-content-score").html("Score: <strong>"+res.data.score+"%</strong>"); $("#optimize-content").show(); } }); }); });');
});
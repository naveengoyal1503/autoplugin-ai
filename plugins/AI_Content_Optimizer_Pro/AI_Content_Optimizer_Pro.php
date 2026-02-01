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
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aco_analyze_content', [$this, 'ajax_analyze']);
        add_action('admin_notices', [$this, 'premium_notice']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium()
        ]);
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco.css', [], '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', [$this, 'meta_box_html'], 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        echo '<div id="aco-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . esc_html($score) . '/100</p>';
        }
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        if (!$this->is_premium()) {
            echo '<p><a href="https://example.com/premium" target="_blank">Upgrade to Premium for AI Rewrite & Keywords</a></p>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium() && isset($_POST['action']) && $_POST['action'] === 'aco_analyze_content') {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count > 500 ? 20 : 0) + (strpos($content, 'keyword') !== false ? 30 : 0));
        update_post_meta($post_id, '_aco_score', $score);
        wp_send_json_success(['score' => $score]);
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock AI rewriting and advanced SEO with <strong>AI Content Optimizer Pro Premium</strong>. <a href="https://example.com/premium">Get it now!</a></p></div>';
        }
    }

    public function activate() {
        add_option('aco_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Dummy JS/CSS placeholders (in real plugin, include actual files)
/*
Placeholder for aco.js:

jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze_content',
            post_id: $('#post_ID').val(),
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#aco-results').html('<p><strong>SEO Score:</strong> ' + response.data.score + '/100</p>');
            } else {
                alert(response.data);
            }
        });
    });
});

Placeholder for aco.css:

#aco-results { padding: 10px; }
#aco-analyze { width: 100%; margin-bottom: 10px; }
*/
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for better SEO and readability with AI-powered suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'isPremium' => false
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        echo '<div id="ai-optimizer-output">';
        echo '<button id="analyze-content" class="button button-primary">Analyze Content</button>';
        echo '<div id="analysis-results"></div>';
        echo '<p><small><strong>Premium:</strong> Unlock bulk optimization, advanced AI suggestions, and exports. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></small></p>';
        echo '</div>';
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $words = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = count($sentences);
        $readability = $words > 0 ? round(206.835 - 1.015 * ($words / $sentences) - 84.6 * (1/ $words), 2) : 0;
        $suggestions = array();
        if ($readability < 60) {
            $suggestions[] = 'Improve readability: Shorten sentences.';
        }
        if (substr_count(strtolower($content), 'keyword') === 0) {
            $suggestions[] = 'Add primary keyword for better SEO.';
        }
        $results = array(
            'word_count' => $words,
            'sentence_count' => $sentences,
            'readability_score' => $readability,
            'suggestions' => $suggestions,
            'premium_tease' => 'Upgrade for AI-generated rewrites and keyword research!'
        );
        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock full AI power with <strong>AI Content Optimizer Pro</strong>: Advanced optimizations, API integrations, and more. <a href="https://example.com/premium">Get Premium</a></p></div>';
    }

    public function activate() {
        set_transient('ai_optimizer_notice', true, 3600);
    }
}

new AIContentOptimizerLite();

// Dummy JS file content (in real plugin, separate file)
/*
In a real deployment, create optimizer.js with:
$(document).ready(function($) {
    $('#analyze-content').click(function() {
        var content = $('#content').val();
        $.post(aiOptimizer.ajaxurl, {
            action: 'optimize_content',
            nonce: aiOptimizer.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                var res = response.data;
                var html = '<p><strong>Words:</strong> ' + res.word_count + '</p>' +
                          '<p><strong>Readability (Flesch):</strong> ' + res.readability_score + '</p>';
                if (res.suggestions.length) {
                    html += '<ul>' + res.suggestions.map(function(s) { return '<li>' + s + '</li>'; }).join('') + '</ul>';
                }
                html += '<p>' + res.premium_tease + '</p>';
                $('#analysis-results').html(html);
            }
        });
    });
});
*/
?>
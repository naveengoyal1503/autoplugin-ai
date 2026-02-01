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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results">';
        if (!empty($content)) {
            $this->basic_analysis($content);
        }
        echo '</div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><a href="https://example.com/premium" target="_blank" class="aco-premium-link">Upgrade to Pro for AI Rewrites & Keywords</a></p>';
    }

    private function basic_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? round(206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (0.846 / $word_count * 100), 2) : 0;
        $score = $this->calculate_score($word_count, $readability);

        echo '<ul>';
        echo '<li><strong>Word Count:</strong> ' . $word_count . '</li>';
        echo '<li><strong>Readability (Flesch):</strong> ' . $readability . '</li>';
        echo '<li><strong>SEO Score:</strong> ' . $score . '%</li>';
        echo '</ul>';
    }

    private function calculate_score($words, $readability) {
        $score = 50;
        if ($words > 300) $score += 20;
        if ($readability > 60) $score += 15;
        if ($words < 100) $score -= 20;
        return min(100, max(0, $score));
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $content = sanitize_textarea_field($_POST['content']);
        $results = array(
            'word_count' => str_word_count(strip_tags($content)),
            'readability' => 65, // Simulated
            'score' => 85, // Simulated
            'tips' => array('Add more subheadings', 'Improve keyword density')
        );

        if (!$this->is_premium()) {
            $results['premium_only'] = 'AI rewrite and keywords available in Pro';
        }

        wp_send_json_success($results);
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_nag() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced features: <a href="https://example.com/premium">Upgrade Now</a></p></div>';
        }
    }

    public function activate() {
        add_option('aco_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Simulated JS file content - in real plugin, separate JS file
/*
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var content = $('#content').val();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                var res = response.data;
                $('#aco-results').html(
                    '<ul><li>Words: ' + res.word_count + '</li>' +
                    '<li>Readability: ' + res.readability + '</li>' +
                    '<li>Score: ' + res.score + '%</li>' +
                    (res.premium_only ? '<li>' + res.premium_only + '</li>' : '') + '</ul>'
                );
            }
        });
    });
});
*/
?>
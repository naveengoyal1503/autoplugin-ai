/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model.
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
        add_action('wp_ajax_ai_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => get_option(self::PREMIUM_KEY, false)
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Free Features:</strong> Readability Score | Word Count | SEO Keywords</p>';
        echo '<button id="analyze-content" class="button">Analyze Content</button>';
        echo '<div id="analysis-results"></div>';
        echo '<p id="premium-upsell" style="display:none;"><strong>Go Premium:</strong> AI Rewrite, Keyword Suggestions - <a href="https://example.com/premium" target="_blank">Upgrade Now ($4.99/mo)</a></p>';
        echo '</div>';
    }

    public function handle_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $content = sanitize_textarea_field($_POST['content']);
        $is_premium = get_option(self::PREMIUM_KEY, false);

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content);

        $results = array(
            'word_count' => $word_count,
            'readability' => number_format($readability, 2),
            'keywords' => implode(', ', array_slice($keywords, 0, 5)),
            'is_premium' => $is_premium
        );

        if ($is_premium) {
            // Simulate premium AI rewrite (in real: API call)
            $results['rewrite'] = $this->mock_ai_rewrite($content);
        }

        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = preg_replace('/[^a-z]/i', '', $text);
        return preg_match_all('/[aeiouy]{2,}/', $text) + preg_match_all('/[aeiouy]/', $text) - preg_match_all('/^[^aeiouy]*/', $text);
    }

    private function extract_keywords($text) {
        $words = explode(' ', strip_tags($text));
        $word_freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($word_freq);
        return array_keys($word_freq);
    }

    private function mock_ai_rewrite($content) {
        return substr($content, 0, 200) . '... (Premium AI Rewrite)';
    }

    public function premium_nag() {
        if (get_option(self::PREMIUM_KEY, false) || !current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features like AI rewriting for $4.99/mo! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }

    public function activate() {
        add_option('ai_content_optimizer_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Mock JS file content (in real plugin, separate file)
/*
Add this to a separate optimizer.js file:

jQuery(document).ready(function($) {
    $('#analyze-content').click(function(e) {
        e.preventDefault();
        var content = $('#content').val() || tinyMCE.activeEditor.getContent();
        $.post(ai_optimizer.ajax_url, {
            action: 'ai_optimize_content',
            nonce: ai_optimizer.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                var res = response.data;
                var html = '<p><strong>Words:</strong> ' + res.word_count + '</p>' +
                          '<p><strong>Readability:</strong> ' + res.readability + '</p>' +
                          '<p><strong>Keywords:</strong> ' + res.keywords + '</p>';
                if (res.rewrite) {
                    html += '<p><strong>AI Rewrite:</strong> ' + res.rewrite + '</p>';
                } else {
                    $('#premium-upsell').show();
                }
                $('#analysis-results').html(html);
            }
        });
    });
});
*/
?>
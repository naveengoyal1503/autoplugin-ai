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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        echo '<div id="aco-container">';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-results"></div>';
        echo '<p><small><strong>Premium:</strong> AI Rewrite, Keyword Suggestions - <a href="https://example.com/premium" target="_blank">Upgrade Now</a></small></p>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, (50 + ($word_count > 300 ? 20 : 0) + ($readability > 60 ? 30 : 0)));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'seo_score' => $seo_score,
            'premium_teaser' => 'Upgrade for AI-powered rewriting and advanced keywords!'
        );

        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $sentences = max(1, $sentence_count);
        $asl = $word_count / $sentences;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]+/', $text, $matches);
        return count($matches);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features: AI rewriting & more. <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
    }

    public function activate() {
        add_option('aco_activated', time());
    }
}

new AIContentOptimizer();

// Inline JS and CSS for single file
add_action('admin_footer-post.php', 'aco_inline_js');
add_action('admin_footer-post-new.php', 'aco_inline_js');
function aco_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            var content = $('#content').val() || tinyMCE.activeEditor.getContent();
            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    $('#aco-results').html(
                        '<p><strong>Word Count:</strong> ' + res.word_count + '</p>' +
                        '<p><strong>Readability (Flesch):</strong> ' + res.readability + '</p>' +
                        '<p><strong>SEO Score:</strong> ' + res.seo_score + '/100</p>' +
                        '<p>' + res.premium_teaser + '</p>'
                    );
                }
            });
        });
    });
    </script>
    <style>
    #aco-container { padding: 10px; }
    #aco-results { margin-top: 10px; background: #f9f9f9; padding: 10px; border-radius: 4px; }
    </style>
    <?php
}

?>
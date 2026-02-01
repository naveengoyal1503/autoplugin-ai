/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and readability. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_lite_activated', true);
    }

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
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'ai-optimizer.css', array(), '1.0.0');
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>AI Content Optimizer Lite</h1><div id="ai-optimizer-panel"><button id="analyze-btn" class="button button-primary">Analyze Current Post</button><div id="results"></div><div class="premium-upsell"><p><strong>Go Premium!</strong> Unlock AI-powered suggestions, bulk optimization, and more. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div></div></div>';
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        if (empty($content)) {
            wp_send_json_error('No content found.');
        }

        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_flesch_reading_ease($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ? round($word_count / $sentence_count, 1) : 0;

        $results = array(
            'word_count' => $word_count,
            'readability_score' => round($readability, 2),
            'avg_sentence_length' => $avg_sentence_length,
            'recommendations' => $this->get_basic_recommendations($word_count, $readability, $avg_sentence_length)
        );

        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count($text, 0);
        $syllables = $this->count_syllables($text);

        if ($sentence_count === 0 || $words === 0) {
            return 0;
        }

        $asl = $words / $sentence_count;
        $asw = $syllables / $words;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy](?![aeiouy])/', $word);
        }
        return $syllables;
    }

    private function get_basic_recommendations($words, $readability, $avg_len) {
        $recs = array();
        if ($words < 300) $recs[] = 'Add more content for better engagement (aim for 500+ words).';
        if ($readability < 60) $recs[] = 'Improve readability: Use shorter sentences and simpler words.';
        if ($avg_len > 25) $recs[] = 'Shorten sentences for better flow.';
        return $recs;
    }
}

new AIContentOptimizerLite();

// Inline JS and CSS for self-contained plugin
function ai_optimizer_assets() {
    ?>
    <style>
    #ai-optimizer-panel { margin: 20px 0; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
    #results { margin-top: 20px; }
    .metric { display: inline-block; margin: 10px; padding: 10px; background: white; border-radius: 5px; }
    .premium-upsell { background: #fff3cd; padding: 15px; margin-top: 20px; border: 1px solid #ffeaa7; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function() {
            var post_id = $('#post_ID').val();
            $.post(ai_optimizer_ajax.ajax_url, {
                action: 'analyze_content',
                post_id: post_id,
                nonce: ai_optimizer_ajax.nonce
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<div class="metric"><strong>Words:</strong> ' + res.word_count + '</div>' +
                               '<div class="metric"><strong>Readability:</strong> ' + res.readability_score + ' (60-70 ideal)</div>' +
                               '<div class="metric"><strong>Avg Sentence:</strong> ' + res.avg_sentence_length + ' words</div>' +
                               '<h4>Recommendations:</h4><ul>';
                    $.each(res.recommendations, function(i, rec) {
                        html += '<li>' + rec + '</li>';
                    });
                    html += '</ul>';
                    $('#results').html(html);
                } else {
                    $('#results').html('<p>Error: ' + response.data + '</p>');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_assets');
add_action('admin_footer-post-new.php', 'ai_optimizer_assets');
?>
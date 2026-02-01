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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_suggestions', true);
        echo '<div id="ai-score">Score: <span id="score-value">' . esc_html($score ?: 'Not analyzed') . '%</span></div>';
        echo '<button id="analyze-content" class="button">Analyze Now</button>';
        if ($suggestions) {
            echo '<div id="suggestions"><h4>Suggestions:</h4><ul>';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1><form method="post" action="options.php">';
        settings_fields('ai_optimizer_options');
        do_settings_sections('ai_optimizer_options');
        submit_button();
        echo '</form></div>';
    }

    public function activate() {
        add_option('ai_optimizer_api_key', '');
    }
}

new AIContentOptimizer();

// AJAX handler for analysis
add_action('wp_ajax_analyze_content', 'handle_ai_analysis');

function handle_ai_analysis() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_optimizer_nonce')) {
        wp_die('Security check failed');
    }

    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    // Simple heuristic-based analysis
    $word_count = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content);
    $sentence_count = count(array_filter($sentences));
    $readability = $word_count > 0 ? min(100, max(0, 100 - abs(($word_count / $sentence_count) - 18) * 3)) : 0;

    $keywords = $this->extract_keywords($content);
    $score = round(($readability + (count($keywords) * 2) + ($word_count > 300 ? 20 : 0)) / 3);

    $suggestions = array();
    if ($word_count < 300) $suggestions[] = 'Add more content (aim for 300+ words).';
    if ($sentence_count < 10) $suggestions[] = 'Break into more sentences for readability.';
    if (count($keywords) < 3) $suggestions[] = 'Include more relevant keywords.';

    update_post_meta($post_id, '_ai_score', $score);
    update_post_meta($post_id, '_ai_suggestions', $suggestions);

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

// Placeholder JS enqueue (inline for single file)
function ai_optimizer_inline_js() {
    if (get_current_screen()->id === 'post') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#analyze-content').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                var nonce = $('#ai_optimizer_nonce').val();
                $.post(ajaxurl, {
                    action: 'analyze_content',
                    post_id: postId,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        $('#score-value').text(response.data.score + '%');
                        var suggHtml = '<h4>Suggestions:</h4><ul>';
                        $.each(response.data.suggestions, function(i, s) {
                            suggHtml += '<li>' + s + '</li>';
                        });
                        suggHtml += '</ul>';
                        $('#suggestions').html(suggHtml);
                    }
                });
            });
        });
        </script>
        <style>
        #ai-score { font-size: 18px; font-weight: bold; color: #0073aa; }
        #suggestions { margin-top: 10px; }
        #suggestions ul { margin: 0; }
        </style>
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');

// Simple keyword extraction
function extract_keywords($content) {
    $words = explode(' ', strip_tags(strtolower($content)));
    $common = array('the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'day', 'get');
    $counts = array();
    foreach ($words as $word) {
        $word = preg_replace('/[^a-z]/', '', $word);
        if (strlen($word) > 3 && !in_array($word, $common)) {
            $counts[$word] = isset($counts[$word]) ? $counts[$word] + 1 : 1;
        }
    }
    arsort($counts);
    return array_slice(array_keys($counts), 0, 5);
}

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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_co_api_key', '');
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-co-js', 'ai_co_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_co_nonce')));
        wp_enqueue_style('ai-co-css', plugin_dir_url(__FILE__) . 'ai-co.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_co_save', 'ai_co_nonce');
        $score = get_post_meta($post->ID, '_ai_co_score', true);
        echo '<div id="ai-co-score">SEO Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="ai-co-analyze" class="button">Analyze Content</button>';
        echo '<button id="ai-co-optimize" class="button button-primary">Optimize</button>';
        echo '<div id="ai-co-suggestions"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_co_nonce']) || !wp_verify_nonce($_POST['ai_co_nonce'], 'ai_co_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // Score saved via AJAX
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simple AI-like analysis
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? min(100, max(0, 100 - ($word_count / $sentence_count - 15) * 5)) : 0;
        $keywords = $this->extract_keywords($content);
        $score = round(($readability + (count($keywords) * 2) + min(50, $word_count / 10)) / 3);

        update_post_meta($post_id, '_ai_co_score', $score);
        update_post_meta($post_id, '_ai_co_keywords', $keywords);

        $suggestions = $this->generate_suggestions($content, $score, $keywords);

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower(preg_replace('/[^a-z\s]/', '', $content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_slice(array_keys($counts), 0, 5);
    }

    private function generate_suggestions($content, $score, $keywords) {
        $sugs = array();
        if ($score < 70) $sugs[] = 'Add more headings (H2/H3) for better structure.';
        if (str_word_count($content) < 300) $sugs[] = 'Aim for 300+ words for optimal SEO.';
        $sugs[] = 'Primary keywords: ' . implode(', ', $keywords);
        $sugs[] = 'Pro Tip: Use short paragraphs and bullet lists for readability.';
        return $sugs;
    }
}

new AIContentOptimizer();

// Enqueue dummy JS/CSS (self-contained)
function ai_co_inline_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    ?>
    <style id="ai-co-css">
    #ai-co-score { margin: 10px 0; font-size: 16px; }
    #ai-co-suggestions { margin-top: 10px; font-size: 12px; color: #666; }
    #ai-co-suggestions ul { list-style: disc; padding-left: 20px; }
    </style>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#ai-co-analyze, #ai-co-optimize').click(function(e) {
            e.preventDefault();
            $.post(ai_co_ajax.ajax_url, {
                action: 'optimize_content',
                nonce: ai_co_ajax.nonce,
                post_id: $('#post_ID').val()
            }, function(res) {
                if (res.success) {
                    $('#ai-co-score strong').text(res.data.score + '%');
                    var html = '<ul>';
                    $.each(res.data.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    $('#ai-co-suggestions').html(html);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_co_inline_scripts');
add_action('admin_footer-post-new.php', 'ai_co_inline_scripts');

// Premium upsell notice
function ai_co_admin_notice() {
    echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Content Optimizer Pro</strong> for advanced AI rewriting and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'ai_co_admin_notice');

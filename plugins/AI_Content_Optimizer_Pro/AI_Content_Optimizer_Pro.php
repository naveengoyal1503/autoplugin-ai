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
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side', 'high');
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>AI Score:</strong> <span id="ai-score">' . esc_html($score ?: 'Not analyzed') . '</span>/100</p>';
        echo '<button id="analyze-content" class="button button-primary">Analyze Content</button>';
        echo '<div id="ai-suggestions">' . esc_html($suggestions ?: '') . '</div>';
        echo '<p><em>Pro: Unlock advanced AI suggestions and auto-optimizations.</em></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<p>Upgrade to Pro for advanced features. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>';
        echo '</div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_handler');
function ai_analyze_content_handler() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

    // Simple heuristic analysis
    $word_count = str_word_count($content);
    $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    $readability = $word_count > 0 ? min(100, ($word_count / max(1, $sentence_count)) * 10) : 0;
    $keywords = $this->extract_keywords($content);
    $score = min(100, ($readability + (count($keywords) * 5)) / 2);

    $suggestions = array();
    if ($word_count < 300) $suggestions[] = 'Add more content (aim for 300+ words).';
    if ($readability < 60) $suggestions[] = 'Improve readability: shorter sentences.';
    if (count($keywords) < 3) $suggestions[] = 'Add primary keywords.';

    update_post_meta($post_id, '_ai_optimizer_score', $score);
    update_post_meta($post_id, '_ai_optimizer_suggestions', implode(' ', $suggestions));

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

// Dummy JS/CSS files would be created separately, but for single-file, inline them
function ai_optimizer_inline_assets() {
    ?>
    <style>
    #ai-optimizer-panel { padding: 10px; }
    #ai-score { color: #0073aa; font-size: 1.2em; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-content').click(function() {
            $.post(ajaxurl, {
                action: 'ai_analyze_content',
                post_id: $('#post_ID').val(),
                nonce: ai_optimizer.nonce
            }, function(response) {
                if (response.success) {
                    $('#ai-score').text(response.data.score);
                    $('#ai-suggestions').html(response.data.suggestions.join('<br>'));
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_assets');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_assets');

// Keyword extraction
function extract_keywords($content) {
    $words = explode(' ', strtolower(strip_tags($content)));
    $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
    arsort($counts);
    return array_slice(array_keys($counts), 0, 5);
}

new AIContentOptimizer();

// Pro upsell notice
function ai_optimizer_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for auto-rewrites, keyword research, and more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_optimizer_pro_notice');
?>
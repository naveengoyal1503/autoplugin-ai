/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO using AI-powered insights. Freemium version with basic features.
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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ai_content_analyze', array($this, 'handle_ajax_analysis'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => false
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
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-results">
                <button id="analyze-content" class="button button-primary">Analyze Content</button>
                <div id="results"></div>
                <p><em>Basic analysis: Word count, readability, keyword density. <a href="https://example.com/premium" target="_blank">Go Premium</a> for AI suggestions!</em></p>
              </div>';
    }

    public function handle_ajax_analysis() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(($word_count / $sentence_count), 1) : 0; // Avg words per sentence
        $keyword = sanitize_text_field($_POST['keyword']);
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $density = $word_count > 0 ? round(($keyword_count / $word_count) * 100, 1) : 0;

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability < 20 ? 'Good' : ($readability < 30 ? 'Average' : 'Poor'),
            'keyword_density' => $density . '%',
            'message' => "Premium users get AI rewrite suggestions and auto-optimization! <a href='https://example.com/premium' target='_blank'>Upgrade Now</a>"
        );

        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock AI-powered content optimization with <strong>AI Content Optimizer Pro</strong>! <a href="https://example.com/premium" target="_blank">Learn More</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    if (get_current_screen()->id !== 'post' && get_current_screen()->id !== 'page') return;
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#analyze-content').click(function() {
        var content = $('#content').val();
        var keyword = prompt('Enter main keyword:');
        if (!keyword) return;

        $('#results').html('<p>Analyzing...</p>');

        $.post(ai_optimizer.ajax_url, {
            action: 'ai_content_analyze',
            post_id: $('#post_ID').val(),
            keyword: keyword,
            nonce: ai_optimizer.nonce
        }, function(response) {
            if (response.success) {
                var res = response.data;
                $('#results').html(
                    '<p><strong>Words:</strong> ' + res.word_count + '</p>' +
                    '<p><strong>Readability:</strong> ' + res.readability + '</p>' +
                    '<p><strong>Keyword Density:</strong> ' + res.keyword_density + '</p>' +
                    res.message
                );
            }
        });
    });
});
</script>
<?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
?>
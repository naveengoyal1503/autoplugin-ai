/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free basic features; upgrade to Pro for advanced AI tools.
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
    const PREMIUM_URL = 'https://example.com/premium-upgrade?ref=plugin';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic SEO & readability score. <a href="' . self::PREMIUM_URL . '" target="_blank">Pro: AI Rewrite & Keywords</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        if (strlen($content) < 100) {
            wp_die(json_encode(array('error' => 'Content too short.')));
        }

        // Basic analysis (free)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - (1.43 * (word_count($content) / $sentence_count)), 2) : 0;
        $seo_score = min(100, (min(500, $word_count) / 5) + ($readability / 2));

        $results = array(
            'word_count' => $word_count,
            'readability_flesch' => $readability,
            'seo_score' => round($seo_score),
            'premium_tease' => 'Upgrade for AI-powered keyword suggestions and auto-rewrite!'
        );

        wp_send_json($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options') || get_option('aco_dismissed_notice')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced features: AI rewriting, keyword research, and more! <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade now</a> | <a href="' . wp_nonce_url(admin_url('admin-post.php?action=aco_dismiss_notice'), 'aco_dismiss') . '">Dismiss</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Dummy JS/CSS files would be base64 or inline, but for single-file, add inline
add_action('admin_head-post.php', 'aco_inline_scripts');
add_action('admin_head-post-new.php', 'aco_inline_scripts');
function aco_inline_scripts() {
    echo '<script>
jQuery(document).ready(function($) {
    $("#aco-analyze").click(function() {
        var post_id = $("#post_ID").val();
        $("#aco-results").html("Analyzing...");
        $.post(aco_ajax.ajax_url, {
            action: "aco_analyze_content",
            post_id: post_id,
            nonce: aco_ajax.nonce
        }, function(response) {
            var data = JSON.parse(response);
            if (data.error) {
                $("#aco-results").html("<p class=\"notice notice-error\">" + data.error + "</p>");
            } else {
                var html = "<p><strong>Words:</strong> " + data.word_count + "</p>" +
                          "<p><strong>Readability (Flesch):</strong> " + data.readability_flesch + "</p>" +
                          "<p><strong>SEO Score:</strong> " + data.seo_score + "/100</p>" +
                          "<p>" + data.premium_tease + " <a href=\"' . esc_js(AIContentOptimizer::PREMIUM_URL) . '\" target=\"_blank\">Upgrade to Pro</a></p>";
                $("#aco-results").html(html);
            }
        });
    });
});
</script>';
    echo '<style>
#aco-results { margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; }
#aco-analyze { width: 100%; margin-bottom: 10px; }
</style>';
}

// Handle dismiss
add_action('admin_post_aco_dismiss_notice', function() {
    if (wp_verify_nonce($_GET['_wpnonce'], 'aco_dismiss')) {
        update_option('aco_dismissed_notice', 1);
    }
    wp_redirect(admin_url());
    exit;
});

// Helper function for word count
function word_count($text) {
    preg_match_all('/\w+/', $text, $matches);
    return count($matches);
}

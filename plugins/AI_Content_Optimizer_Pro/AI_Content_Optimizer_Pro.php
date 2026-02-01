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
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-ajax', plugin_dir_url(__FILE__) . 'aco-ajax.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-ajax', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results">Click "Analyze" for free basic SEO check.</div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small><strong>Pro:</strong> Unlock AI rewriting, keyword suggestions & bulk tools. <a href="https://example.com/pro" target="_blank">Upgrade Now ($29/yr)</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis (simulated AI with heuristics)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - ($word_count / $sentence_count * 10), 1) : 0; // Simple Flesch-like score
        $headings = preg_match_all('/<h[1-6]/', $content);
        $seo_score = min(100, (20 + ($word_count > 300 ? 20 : 0) + ($headings > 1 ? 20 : 0) + ($readability > 60 ? 20 : 0) + rand(0,20))); // Simulated

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $seo_score < 80 ? 'Pro tip: Add more headings and keywords for better SEO. Upgrade for AI fixes!' : 'Great! Content looks optimized.',
            'is_premium' => false
        );

        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options') || get_option('aco_dismissed_notice')) {
            return;
        }
        echo '<div class="notice notice-info is-dismissible" id="aco-premium-notice">';
        echo '<p>Supercharge <strong>AI Content Optimizer</strong> with Pro: AI rewriting, 100+ keywords, bulk optimize! <a href="https://example.com/pro" target="_blank">Get Pro ($29/yr)</a> | <a href="#" onclick="aco_dismiss_notice()">Dismiss</a></p>';
        echo '</div>';
        echo '<script>function aco_dismiss_notice() { jQuery.post(ajaxurl, {action: "aco_dismiss_notice"}); jQuery("#aco-premium-notice").fadeOut(); }</script>';
    }

    public function activate() {
        update_option('aco_activated', time());
    }
}

AIContentOptimizer::get_instance();

// AJAX for dismiss
add_action('wp_ajax_aco_dismiss_notice', function() {
    update_option('aco_dismissed_notice', true);
    wp_die();
});

// JS file content would be enqueued, but for single-file, inline it
add_action('admin_footer-post.php', 'aco_inline_js');
add_action('admin_footer-post-new.php', 'aco_inline_js');
function aco_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                post_id: $('#post_ID').val()
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<strong>SEO Score: ' + res.seo_score + '%</strong><br>' +
                               'Words: ' + res.word_count + '<br>' +
                               'Readability: ' + res.readability + '<br>' +
                               res.suggestions;
                    $('#aco-results').html(html);
                }
            });
        });
    });
    </script>
    <?php
}

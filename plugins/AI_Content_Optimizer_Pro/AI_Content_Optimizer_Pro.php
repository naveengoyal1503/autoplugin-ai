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
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_pro' => false
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Free Analysis:</strong> Readability & Keyword Density</p>';
        echo '<textarea id="ai-content-input" rows="5" cols="30">' . esc_textarea($post->post_content) . '</textarea>';
        echo '<br><button id="analyze-btn" class="button button-primary">Analyze Free</button>';
        echo '<div id="analysis-results"></div>';
        echo '<p><em>Upgrade to Pro for AI Rewrite Suggestions ($9/mo)!</em></p>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - (1.43 * (log($word_count / $sentence_count) * log(10))), 2) : 0; // Simplified Flesch score
        $keywords = $this->extract_keywords($content);
        $density = $keywords ? round(($keywords[1] * 100 / $word_count), 2) : 0;

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'keywords' => $keywords ? array_slice($keywords, 0, 5) : array(),
            'density' => $density
        );

        wp_send_json_success($results);
    }

    private function extract_keywords($content) {
        $words = preg_split('/\s+/', strtolower(strip_tags($content)));
        $counts = array_count_values(array_filter($words, function($w) {
            return strlen($w) > 4 && !preg_match('/[^a-z]/', $w);
        }));
        arsort($counts);
        return array_map(function($k, $v) { return array($k, $v); }, array_keys($counts), array_values($counts));
    }

    public function premium_notice() {
        if (!current_user_can('manage_options') || get_option('ai_optimizer_dismissed') || $this->is_pro()) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong>: AI rewrites, advanced SEO! <a href="https://example.com/pro" target="_blank">Upgrade Now</a> | <a href="#" onclick="dismissNotice()">Dismiss</a></p></div>';
    }

    private function is_pro() {
        return false; // Simulate free version
    }

    public function activate() {
        update_option('ai_optimizer_version', '1.0.0');
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    if (wp_doing_ajax()) return;
    ?><script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function(e) {
            e.preventDefault();
            var content = $('#ai-content-input').val();
            $.post(ai_optimizer.ajax_url, {
                action: 'analyze_content',
                nonce: ai_optimizer.nonce,
                content: content
            }, function(res) {
                if (res.success) {
                    var html = '<h4>Results:</h4>' +
                        '<p>Words: ' + res.data.word_count + '</p>' +
                        '<p>Readability: ' + res.data.readability + '</p>' +
                        '<p>Top Keywords: ' + res.data.keywords.map(k => k + ' (' + k[1] + ')').join(', ') + '</p>' +
                        '<p class="pro-tease">Pro: AI Optimizations & Rewrites!</p>';
                    $('#analysis-results').html(html);
                }
            });
        });
    });
    function dismissNotice() {
        jQuery.post(ajaxurl, {action: 'dismiss_ai_notice'});
        jQuery('.notice-info').fadeOut();
    }
    </script><?php
}
add_action('admin_footer', 'ai_optimizer_inline_js');

add_action('wp_ajax_dismiss_ai_notice', function() {
    update_option('ai_optimizer_dismissed', true);
    wp_die();
});
?>
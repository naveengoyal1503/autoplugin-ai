/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability with AI-powered insights. Premium version available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
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
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic readability score. <a href="https://example.com/premium" target="_blank">Go Premium</a> for AI suggestions!</small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate basic analysis (lite version)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - (120 * ($word_count / $sentence_count)), 2) : 0; // Flesch approximation

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'score' => $readability > 60 ? 'Good' : ($readability > 30 ? 'Fair' : 'Poor'),
            'message' => 'Upgrade to premium for AI keyword suggestions and auto-optimizations!'
        );

        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ($screen->id === 'plugin-install') {
            echo '<div class="notice notice-info"><p>Unlock advanced AI features in <strong>AI Content Optimizer Pro</strong> – <a href="https://example.com/premium" target="_blank">Upgrade now</a> for bulk optimization & SEO boosts!</p></div>';
        }
    }

    public function activate() {
        set_transient('aco_premium_notice', true, WEEK_IN_SECONDS);
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function aco_inline_js() {
    if (get_current_screen()->id !== 'post') return;
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var post_id = $('#post_ID').val();
        $('#aco-results').html('<p>Analyzing...</p>');
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            post_id: post_id,
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                var res = response.data;
                $('#aco-results').html(
                    '<p><strong>Words:</strong> ' + res.word_count + '</p>' +
                    '<p><strong>Readability:</strong> ' + res.score + ' (' + res.readability + ')</p>' +
                    '<p>' + res.message + '</p>'
                );
            }
        });
    });
});
</script>
<?php
}
add_action('admin_footer-post.php', 'aco_inline_js');
add_action('admin_footer-post-new.php', 'aco_inline_js');
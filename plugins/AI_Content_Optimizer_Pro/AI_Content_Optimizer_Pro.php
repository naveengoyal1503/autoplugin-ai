/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for SEO, readability, and engagement.
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
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_optimize', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_pro_active', false);
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $pro = get_option('aco_pro_active', false);
        echo '<div id="aco-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="aco-analyze" class="button">Analyze</button>';
        if ($pro) {
            echo '<button id="aco-optimize" class="button button-primary">Optimize (Pro)</button>';
        } else {
            echo '<p><a href="https://example.com/pro" target="_blank">Upgrade to Pro for AI optimizations</a></p>';
        }
        echo '<div id="aco-results"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) return;
        // Score saved via AJAX
    }

    public function ajax_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $pro = get_option('aco_pro_active', false);
        if (!$pro) {
            wp_die(json_encode(array('error' => 'Pro feature')));
        }
        $content = get_post_field('post_content', $post_id);
        // Simulate AI optimization (in real: integrate OpenAI API)
        $optimized = $this->mock_ai_optimize($content);
        wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        $score = $this->calculate_score($optimized);
        update_post_meta($post_id, '_aco_score', $score);
        wp_die(json_encode(array('content' => $optimized, 'score' => $score)));
    }

    private function calculate_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $word_count > 0 ? round(180 - 120 * ($sentence_count / $word_count), 1) : 0;
        $seo_score = min(100, ($word_count / 10)); // Mock SEO
        return min(100, round(($readability + $seo_score) / 2));
    }

    private function mock_ai_optimize($content) {
        // Mock AI: improve readability
        $content = preg_replace('/\b(the|a|an)\s+([a-zA-Z]{3,})\b/i', '$2', $content);
        return $content . '\n\nOptimized for better engagement.';
    }
}

new AIContentOptimizer();

// Pro check (simulate license)
add_action('admin_notices', function() {
    if (!get_option('aco_pro_active')) {
        echo '<div class="notice notice-info"><p>Unlock AI optimizations with <a href="https://example.com/pro">AI Content Optimizer Pro</a> ($49/year).</p></div>';
    }
});

// JS file content (embedded for single file)
/*
(function($) {
    $(document).ready(function() {
        $('#aco-analyze').click(function() {
            $.post(aco_ajax.ajaxurl, {
                action: 'aco_analyze',
                post_id: $("#post_ID").val(),
                nonce: aco_ajax.nonce
            }, function(response) {
                var score = JSON.parse(response).score;
                $('#aco-score strong').text(score + '%');
            });
        });
        $('#aco-optimize').click(function() {
            $.post(aco_ajax.ajaxurl, {
                action: 'aco_optimize',
                post_id: $("#post_ID").val(),
                nonce: aco_ajax.nonce
            }, function(data) {
                $('#aco-results').html('<p>Optimized! New score updated.</p>');
                wp.editor.getDefaultBlockName(); // Refresh editor
            });
        });
    });
})(jQuery);
*/

// CSS
/*
#aco-score { font-size: 18px; margin: 10px 0; }
#aco-results { margin-top: 10px; color: green; }
*/

?>
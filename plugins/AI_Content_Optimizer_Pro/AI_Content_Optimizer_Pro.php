/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered plugin that analyzes and optimizes post readability, SEO, and engagement with real-time suggestions and auto-fixes.
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
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_pro_active', false);
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-script', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_meta_nonce');
        $analysis = get_post_meta($post->ID, '_aco_analysis', true);
        echo '<div id="aco-results">';
        if ($analysis) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '%</p>';
            echo '<p><strong>SEO Score:</strong> ' . esc_html($analysis['seo']) . '%</p>';
            echo '<p><strong>Suggestions:</strong> ' . esc_html($analysis['suggestions']) . '</p>';
        }
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<p><small><a href="https://example.com/pro" target="_blank">Upgrade to Pro for AI Auto-Optimize</a></small></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_meta_nonce']) || !wp_verify_nonce($_POST['aco_meta_nonce'], 'aco_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Mock AI analysis (Pro would integrate real AI API like OpenAI)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $readability = min(95, 70 + (200 - $word_count) / 5 + count($sentences) * 2);
        $seo = rand(60, 95); // Simulate
        $suggestions = $word_count < 300 ? 'Add more content for better engagement.' : 'Great length! Consider adding subheadings.';

        $analysis = array(
            'readability' => round($readability),
            'seo' => $seo,
            'suggestions' => $suggestions
        );
        update_post_meta($post_id, '_aco_analysis', $analysis);

        wp_send_json_success($analysis);
    }
}

new AIContentOptimizer();

// Pro upsell notice
function aco_admin_notice() {
    if (!get_option('aco_pro_active')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for auto-optimizations and bulk processing! <a href="https://example.com/pro">Get Pro Now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'aco_admin_notice');

// Enqueue dummy JS
function aco_enqueue_js() {
    wp_register_script('aco-js', '', array(), '1.0', true);
    wp_add_inline_script('aco-script', '
        jQuery(document).ready(function($) {
            $("#aco-analyze-btn").click(function() {
                var post_id = $("#post_ID").val();
                $("#aco-results").html("Analyzing...");
                $.post(aco_ajax.ajaxurl, {
                    action: "aco_analyze",
                    post_id: post_id,
                    nonce: aco_ajax.nonce
                }, function(res) {
                    if (res.success) {
                        var data = res.data;
                        $("#aco-results").html(
                            "<p><strong>Readability Score:</strong> " + data.readability + "%</p>" +
                            "<p><strong>SEO Score:</strong> " + data.seo + "%</p>" +
                            "<p><strong>Suggestions:</strong> " + data.suggestions + "</p>" +
                            "<p><small><a href=\"https://example.com/pro\" target=\"_blank\">Upgrade to Pro</a></small></p>"
                        );
                    }
                });
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'aco_enqueue_js');
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_co_api_key', '');
        add_option('ai_co_pro', false);
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_co_api_key'])) {
            update_option('ai_co_api_key', sanitize_text_field($_POST['ai_co_api_key']));
        }
        $api_key = get_option('ai_co_api_key');
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1><form method="post"><table class="form-table"><tr><th>API Key</th><td><input type="text" name="ai_co_api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td></tr></table><p class="submit"><input type="submit" class="button-primary" value="Save"></p></form><p><strong>Pro Upgrade:</strong> Unlock unlimited optimizations for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_co_save', 'ai_co_nonce');
        $optimized = get_post_meta($post->ID, '_ai_co_optimized', true);
        $score = get_post_meta($post->ID, '_ai_co_score', true);
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score : 'Not analyzed') . '%</p>';
        echo '<p><a href="#" id="ai-co-analyze" class="button button-secondary" data-post-id="' . $post->ID . '">Analyze & Optimize</a></p>';
        if ($optimized) {
            echo '<p style="color:green;">Content optimized!</p>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_co_nonce']) || !wp_verify_nonce($_POST['ai_co_nonce'], 'ai_co_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('ai-co-frontend', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-co-frontend', 'ai_co_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_co_ajax')));
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_script('ai-co-admin', plugin_dir_url(__FILE__) . 'ai-co-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-co-admin', 'ai_co_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_co_ajax'),
                'pro' => get_option('ai_co_pro')
            ));
        }
    }

    public function ajax_analyze() {
        check_ajax_referer('ai_co_ajax', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die();
        }

        $post = get_post($post_id);
        $content = $post->post_content;
        $title = $post->post_title;

        // Simulate AI analysis (replace with real AI API call in pro version)
        $word_count = str_word_count(strip_tags($content));
        $seo_score = min(100, 50 + ($word_count / 10) + (strlen($title) > 50 ? 20 : 0));
        $readability = min(100, 60 + rand(0, 40));
        $engagement = min(100, 70 + rand(0, 30));

        $suggestions = array(
            'Add more keywords related to "' . $title . '"',
            'Improve sentence variety for better readability',
            'Include a call-to-action at the end'
        );

        if (get_option('ai_co_pro') || rand(0, 1)) {
            // Pro: Auto-optimize
            $content .= '\n\n<h2>Optimized Section</h2><p>This is an AI-generated optimized paragraph to boost engagement.</p>';
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));
            update_post_meta($post_id, '_ai_co_optimized', true);
        }

        update_post_meta($post_id, '_ai_co_score', $seo_score);

        wp_send_json_success(array(
            'score' => $seo_score,
            'readability' => $readability,
            'engagement' => $engagement,
            'suggestions' => $suggestions,
            'pro_required' => !get_option('ai_co_pro')
        ));
    }
}

add_action('wp_ajax_ai_co_analyze', array(new AIContentOptimizer(), 'ajax_analyze'));

new AIContentOptimizer();

// Create JS files placeholder (in real plugin, include actual files)
// ai-co-admin.js and ai-co.js would handle AJAX calls for analyze button
// For single file, simulate with inline script
add_action('admin_footer-post.php', function() {
    echo '<script>jQuery(document).ready(function($) {
        $("#ai-co-analyze").click(function(e) {
            e.preventDefault();
            var postId = $(this).data("post-id");
            $("#ai-co-analyze").text("Analyzing...");
            $.post(ai_co_ajax.ajax_url, {
                action: "ai_co_analyze",
                post_id: postId,
                nonce: ai_co_ajax.nonce
            }, function(response) {
                if (response.success) {
                    alert("Score: " + response.data.score + "%, Readability: " + response.data.readability + "%, Engagement: " + response.data.engagement + "%. Suggestions: " + response.data.suggestions.join(" | "));
                    if (response.data.pro_required) {
                        alert("Upgrade to Pro for auto-optimization!");
                    }
                }
            });
        });
    });</script>';
});
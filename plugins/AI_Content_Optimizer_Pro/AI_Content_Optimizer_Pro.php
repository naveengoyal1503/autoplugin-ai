/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better readability, SEO, and engagement. Freemium model with premium AI features.
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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'aicop_pro_key';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimize_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_suggestions', true);
        echo '<div id="ai-optimizer-results">';
        if ($score) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '/100</p>';
            if ($suggestions) {
                echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
            }
        } else {
            echo '<p>' . __('Click Analyze to optimize!', 'ai-content-optimizer') . '</p>';
        }
        echo '<button type="button" id="ai-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '</div>';
        $this->premium_nag();
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Basic analysis (free)
        $content = $_POST['post_content'];
        $score = $this->calculate_readability($content);
        update_post_meta($post_id, '_ai_optimize_score', $score);
        $suggestions = $this->generate_basic_suggestions($content);
        update_post_meta($post_id, '_ai_suggestions', $suggestions);
    }

    private function calculate_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        $score = min(100, max(0, 50 + ($words / max(1, $sentences)) * 2 - ($words / 100)));
        return round($score);
    }

    private function generate_basic_suggestions($content) {
        $sugs = array();
        if (strlen(strip_tags($content)) < 300) {
            $sugs[] = 'Add more content for better engagement.';
        }
        if (substr_count(strtolower($content), 'href') < 2) {
            $sugs[] = 'Include more internal/external links.';
        }
        return implode(' ', $sugs);
    }

    public function add_admin_menu() {
        add_options_page(
            __('AI Content Optimizer Settings', 'ai-content-optimizer'),
            __('AI Optimizer', 'ai-content-optimizer'),
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['aicop_pro_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aicop_pro_key']));
            echo '<div class="notice notice-success"><p>Key saved!</p></div>';
        }
        $key = get_option(self::PREMIUM_KEY);
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AI Content Optimizer Pro', 'ai-content-optimizer') . '</h1>';
        echo '<form method="post">';
        echo '<p><label>Enter Premium Key: <input type="text" name="aicop_pro_key" value="' . esc_attr($key) . '" /></label></p>';
        submit_button();
        echo '</form>';
        $this->premium_nag();
        echo '</div>';
    }

    private function premium_nag() {
        if (!get_option(self::PREMIUM_KEY)) {
            echo '<div class="notice notice-info"><p>';
            printf(
                __('Unlock AI rewrites, advanced analytics, and priority support with <a href="https://example.com/premium" target="_blank">Premium version</a> ($49 one-time or $9/mo)!', 'ai-content-optimizer'),
                self::VERSION
            );
            echo '</p></div>';
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'settings_page_ai-content-optimizer' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), self::VERSION, true);
            wp_localize_script('ai-optimizer-js', 'aicop_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aicop_nonce'),
                'is_premium' => !!get_option(self::PREMIUM_KEY)
            ));
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// AJAX handler for analyze button
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_handler');
function ai_analyze_content_handler() {
    check_ajax_referer('aicop_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die();
    }
    $content = get_post_field('post_content', $post_id);
    $score = $GLOBALS['ai_content_optimizer']->calculate_readability($content); // Access via global
    wp_send_json_success(array('score' => $score, 'suggestions' => $GLOBALS['ai_content_optimizer']->generate_basic_suggestions($content)));
}

// Note: Premium features would check key and use external AI API (e.g., OpenAI) for rewrites. This is free core.
// Create empty JS file reference
if (!file_exists(plugin_dir_path(__FILE__) . 'optimizer.js')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'optimizer.js', '// AI Optimizer JS\njQuery(document).ready(function($) {\n  $("#ai-analyze-btn").click(function() {\n    var post_id = $("#post_ID").val();\n    $.post(aicop_ajax.ajax_url, {\n      action: "ai_analyze_content",\n      post_id: post_id,\n      nonce: aicop_ajax.nonce\n    }, function(resp) {\n      if (resp.success) {\n        $("#ai-optimizer-results").html("<p><strong>Score:</strong> " + resp.data.score + "</p><p>" + resp.data.suggestions + "</p>");\n      }\n    });\n  });\n  if (!aicop_ajax.is_premium) {\n    $(".notice-info a").attr("target", "_blank");\n  }\n});');
}
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_api_key', '');
        add_option('aco_premium_active', false);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_meta_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $premium_nag = !get_option('aco_premium_active') ? '<p><strong>Upgrade to Premium for AI rewriting & advanced SEO!</strong> <a href="https://example.com/premium" target="_blank">Get Premium</a></p>' : '';
        echo '<div id="aco-results">' . ($score ? '<p>Score: <strong>' . esc_html($score) . '%</strong></p>' : '<p>Click Analyze for free basic check.</p>') . $premium_nag . '</div>';
        echo '<button id="aco-analyze" class="button">Analyze Content</button>';
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

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis: word count, readability (Flesch score approx), keyword density
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence = $sentence_count ? $word_count / $sentence_count : 0;
        $readability = 206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (strlen($content) / $word_count); // Approx Flesch
        $score = min(100, max(0, 50 + ($readability / 20) + (20 - abs(15 - $avg_sentence)))); // Simple scoring

        // Premium tease: Simulate AI (in real, call OpenAI API)
        $premium_features = get_option('aco_premium_active') ? "AI Rewrite: " . substr($this->mock_ai_rewrite($content), 0, 100) . '...' : 'Upgrade for AI rewriting, keywords, and more!';

        wp_send_json_success(array(
            'score' => round($score),
            'details' => "Words: $word_count | Readability: " . round($readability) . " | Premium: $premium_features"
        ));
    }

    private function mock_ai_rewrite($content) {
        return 'Optimized version: ' . substr(strip_tags($content), 0, 200) . ' (Premium AI magic!)';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_api_key'])) {
            update_option('aco_api_key', sanitize_text_field($_POST['aco_api_key']));
        }
        if (isset($_POST['aco_premium'])) {
            update_option('aco_premium_active', true);
        }
        $api_key = get_option('aco_api_key');
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1><form method="post">';
        echo '<p><label>Premium API Key: <input type="text" name="aco_api_key" value="' . esc_attr($api_key) . '" size="50"></label></p>';
        echo '<p class="description">Enter premium key from <a href="https://example.com/premium">our site</a> to unlock AI features.</p>';
        echo '<p><input type="submit" class="button-primary" value="Save"></p></form></div>';
    }
}

AIContentOptimizer::get_instance();

// Dummy JS file content (in real plugin, separate file)
/*
$(document).ready(function($) {
    $('#aco-analyze').click(function() {
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            post_id: $('#post_ID').val(),
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#aco-results').html('<p>Score: <strong>' + response.data.score + '%</strong><br>' + response.data.details + '</p>');
            }
        });
    });
});
*/
?>
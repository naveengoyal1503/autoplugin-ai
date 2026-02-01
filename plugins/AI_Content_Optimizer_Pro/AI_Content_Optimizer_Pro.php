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
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        echo '<textarea id="ai-suggestions" rows="10" cols="30" readonly style="width:100%;"></textarea>';
        echo '<br><button id="analyze-content" class="button button-primary">Analyze & Optimize</button>';
        echo '<p><small>Pro: Bulk optimize entire site.</small></p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_optimizer_nonce')) {
            wp_die('Security check failed');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simple AI-like analysis (rule-based heuristics)
        $suggestions = $this->analyze_content($content);

        wp_send_json_success($suggestions);
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? round(206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (0.846 / avg_word_length($content)), 2) : 0;

        $suggestions = array();
        if ($word_count < 300) $suggestions[] = 'Expand content to at least 300 words for better SEO.';
        if ($readability > 60) $suggestions[] = 'Improve readability: Use shorter sentences (aim for Flesch score 60-70).';
        if (substr_count(strtolower($content), 'keyword') === 0) $suggestions[] = 'Add primary keyword 1-2% density.';
        $suggestions[] = 'Pro Tip: Add H2/H3 headings for structure.';
        $suggestions[] = "Word count: $word_count | Readability: $readability";

        return implode('\n', $suggestions);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

function avg_word_length($content) {
    $words = explode(' ', strip_tags($content));
    $total = 0;
    $count = 0;
    foreach ($words as $word) {
        $len = strlen($word);
        if ($len > 0) {
            $total += $len;
            $count++;
        }
    }
    return $count > 0 ? $total / $count : 0;
}

new AIContentOptimizer();

// Freemium upsell notice
function ai_optimizer_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for bulk optimization and analytics - <a href="https://example.com/pro">Upgrade now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ai_optimizer_notice');

// Dummy JS file content (in real plugin, separate file)
/*
jQuery(document).ready(function($) {
    $('#analyze-content').click(function() {
        $.post(ai_optimizer.ajax_url, {
            action: 'optimize_content',
            post_id: $('#post_ID').val(),
            nonce: $('#ai_optimizer_nonce').val()
        }, function(response) {
            if (response.success) {
                $('#ai-suggestions').val(response.data);
            }
        });
    });
});
*/
?>
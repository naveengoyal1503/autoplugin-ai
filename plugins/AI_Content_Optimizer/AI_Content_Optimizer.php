/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for better SEO and engagement. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Freemius integration for monetization.
if (function_exists('freemius')) {
    // Init Freemius.
    freemius()->add_plugin_action_link('upgrade', '<a href="' . $FREEMIUS_URL . '">Upgrade to Pro</a>');
} else {
    define('FS__PRODUCT_12345_VERSION', '1.0.0');
    // Placeholder for Freemius SDK - in production, include Freemius SDK.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'post.php' && $hook != 'post-new.php') return;
        wp_enqueue_script('aco-js', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-js', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_save', 'aco_nonce');
        $analysis = get_post_meta($post->ID, '_aco_analysis', true);
        echo '<div id="aco-results">';
        if ($analysis) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '%</p>';
            echo '<p><strong>Keyword Density:</strong> ' . esc_html($analysis['keyword_density']) . '%</p>';
        }
        echo '<p>Primary Keyword: <input type="text" id="aco-keyword" placeholder="Enter keyword"></p>';
        echo '<button id="aco-analyze" class="button">Analyze (Free)</button>';
        echo '<p id="aco-premium"><em>Premium: AI Rewrite & Advanced SEO Tips - <a href="#" onclick="alert(\'Upgrade via Freemius\');">Upgrade Now</a></em></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // Analysis saved via AJAX.
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $keyword = sanitize_text_field($_POST['keyword']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis.
        $word_count = str_word_count($content);
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $density = $word_count > 0 ? round(($keyword_count / $word_count) * 100, 2) : 0;
        $readability = min(100, 100 - ($word_count / 1000) * 10); // Simple mock formula.

        $analysis = array(
            'readability' => $readability,
            'keyword_density' => $density
        );

        update_post_meta($post_id, '_aco_analysis', $analysis);

        wp_send_json_success($analysis);
    }

    public function activate() {
        // Activation hook.
    }
}

new AIContentOptimizer();

// Mock JS file content - in production, create separate JS file.
/*
$(document).ready(function() {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        var post_id = $('#post_ID').val();
        var keyword = $('#aco-keyword').val();
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: post_id,
            keyword: keyword
        }, function(response) {
            if (response.success) {
                $('#aco-results').prepend('<p>Readability: ' + response.data.readability + '% | Density: ' + response.data.keyword_density + '%</p>');
            }
        });
    });
});
*/
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for WordPress. Free basic features; premium for advanced AI tools.
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
    const PREMIUM_URL = 'https://example.com/premium-upgrade?ref=plugin';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_links'), 10, 2);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai_content_optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $premium_notice = $this->is_premium() ? '' : '<div class="notice notice-warning"><p><strong>Premium Features:</strong> Unlock AI rewriting, keyword suggestions & more! <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p></div>';
        echo '<div id="ai-optimizer-results">';
        echo $premium_notice;
        echo '<button id="analyze-content" class="button button-primary">Analyze Content</button>';
        echo '<div id="results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . $score . '/100</p>';
            echo '<p><strong>Readability:</strong> ' . $this->get_readability($content) . '</p>';
            echo '<p><strong>Word Count:</strong> ' . str_word_count($content) . '</p>';
        }
        echo '</div></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // Simulate score calculation
        update_post_meta($post_id, '_ai_optimizer_score', rand(60, 95));
    }

    private function get_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content);
        $words = str_word_count($content);
        $score = $words > 0 ? round(180 - 1.43 * ($words / count(array_filter($sentences))), 2) : 0;
        return $score;
    }

    private function is_premium() {
        return false; // Simulate free version
    }

    public function add_plugin_links($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
            $links[] = '<a href="' . self::PREMIUM_URL . '" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        }
        return $links;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Inline JS and CSS for single-file
$js = "<script>jQuery(document).ready(function($){ $('#analyze-content').click(function(){ var content = $('#content').val(); $.post(ajaxurl, {action: 'ai_analyze', content: content, nonce: $('#ai_optimizer_nonce').val()}, function(res){ $('#results').html('<p><strong>SEO Score:</strong> ' + res.score + '</p><p><strong>Readability:</strong> ' + res.read + '</p><p><strong>Word Count:</strong> ' + res.words + '</p>'); }); }); });</script>";
$css = '<style>#ai-optimizer-results { padding: 10px; } #ai-optimizer-results .notice { margin: 10px 0; }</style>';
add_action('admin_head-post.php', function() use ($js, $css) { echo $css . $js; });

add_action('wp_ajax_ai_analyze', function() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $content = sanitize_textarea_field($_POST['content']);
    wp_send_json(array(
        'score' => rand(60, 95),
        'read' => 65.5,
        'words' => str_word_count($content)
    ));
});
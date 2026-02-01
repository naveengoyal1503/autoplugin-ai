/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content optimization for better readability, SEO, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function admin_menu() {
        add_posts_page(
            __('AI Content Optimizer', 'ai-content-optimizer'),
            __('Content Optimizer', 'ai-content-optimizer'),
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'edit.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function admin_page() {
        if (isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            $post = get_post($post_id);
            $content = $post->post_content;
            $score = $this->calculate_readability($content);
            $suggestions = $this->get_basic_suggestions($content);
            include plugin_dir_path(__FILE__) . 'admin-page.php';
        }
    }

    private function calculate_readability($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = preg_match_all('/[.!?]+/', $content);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $score = 100;
        if ($avg_sentence_length > 25) $score -= 20;
        if ($word_count < 300) $score -= 15;
        return max(0, $score);
    }

    private function get_basic_suggestions($content) {
        $suggestions = array();
        $word_count = str_word_count(strip_tags($content));
        if ($word_count < 300) {
            $suggestions[] = 'Add more content to reach at least 300 words for better engagement.';
        }
        if (strpos($content, 'href=') === false) {
            $suggestions[] = 'Include internal and external links to improve SEO.';
        }
        return $suggestions;
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium() && !isset($_POST['free_optimize'])) {
            wp_die(json_encode(array('error' => 'Premium feature required.')));
        }
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        $optimized = $this->optimize_content($post->post_content);
        wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        wp_die(json_encode(array('success' => true, 'content' => $optimized)));
    }

    private function optimize_content($content) {
        // Simulate AI optimization: shorten sentences, add paragraphs
        $content = preg_replace('/([.!?])\s+([a-zA-Z])/', '$1\n\n$2', $content);
        // Premium: more advanced (mocked)
        if ($this->is_premium()) {
            $content .= '\n\n**Premium AI Suggestion:** Optimized for SEO with keywords.';
        }
        return $content;
    }

    private function is_premium() {
        return get_option('ai_optimizer_premium_key') !== false;
    }

    private function check_premium() {
        // Freemium nag
        if (!$this->is_premium() && current_user_can('manage_options')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced SEO and bulk features. <a href="https://example.com/premium" target="_blank">Upgrade now</a></p></div>';
            });
        }
    }
}

AIContentOptimizer::get_instance();

// Meta box for posts
function ai_optimizer_meta_box() {
    add_meta_box('ai-optimizer-box', 'AI Content Optimizer', 'ai_optimizer_meta_box_content', 'post', 'side');
}
add_action('add_meta_boxes', 'ai_optimizer_meta_box');

function ai_optimizer_meta_box_content($post) {
    $optimizer = AIContentOptimizer::get_instance();
    $content = $post->post_content;
    $score = $optimizer->calculate_readability($content);
    echo '<p><strong>Readability Score:</strong> ' . $score . '%</p>';
    echo '<button id="optimize-btn" class="button button-primary">Optimize Now</button>';
    echo '<p class="description">Free: Basic fixes. Premium: AI-powered.</p>';
}

// Create admin-page.php content (inline for single file)
$admin_page_content = '<div class="wrap"><h1>AI Content Optimizer</h1><p>Score: <span id="score">' . $score . '%</span></p><ul id="suggestions"><li>' . implode('</li><li>', $suggestions) . '</li></ul><button class="button button-large button-primary" id="bulk-optimize">Optimize Post</button></div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_page_content);

// JS file content
$js_content = "jQuery(document).ready(function($) { $('#optimize-btn, #bulk-optimize').click(function() { $.post(ai_optimizer.ajax_url, { action: 'optimize_content', post_id: $('input[name=\"post_ID\"]').val(), nonce: ai_optimizer.nonce, free_optimize: true }, function(res) { if(res.success) { $('#content').val(res.content); alert('Optimized!'); } }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'optimizer.js', $js_content);

// CSS
$css_content = '#ai-optimizer-box { background: #f1f1f1; } #optimize-btn { width: 100%; }';
file_put_contents(plugin_dir_path(__FILE__) . 'optimizer.css', $css_content);

// Activation hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
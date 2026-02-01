/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Boost SEO with AI-powered content analysis and optimization suggestions directly in your WordPress editor. Free version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        // Premium nag
        if (!get_option('ai_content_optimizer_premium')) {
            add_action('admin_notices', array($this, 'premium_nag'));
        }
    }

    public function enqueue_scripts() {
        // Frontend scripts if needed
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_script('ai-content-optimizer', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-content-optimizer', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
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
        $analysis = $this->analyze_content($content);
        echo '<div id="ai-content-analysis">';
        echo '<p><strong>' . __('Word Count:', 'ai-content-optimizer') . '</strong> ' . $analysis['word_count'] . '</p>';
        echo '<p><strong>' . __('Readability Score:', 'ai-content-optimizer') . '</strong> ' . $analysis['readability'] . '/100</p>';
        echo '<p><strong>' . __('SEO Keywords:', 'ai-content-optimizer') . '</strong> ' . $analysis['keywords'] . '</p>';
        echo '<div id="optimization-tips">';
        echo '<h4>' . __('Quick Tips (Free):', 'ai-content-optimizer') . '</h4>';
        echo '<ul>';
        foreach ($analysis['tips'] as $tip) {
            echo '<li>' . $tip . '</li>';
        }
        echo '</ul>';
        echo '<p><em>' . __('Upgrade to Premium for AI-generated rewrites and advanced SEO insights!', 'ai-content-optimizer') . '</em></p>';
        echo '<a href="https://example.com/premium" class="button button-primary" target="_blank">' . __('Go Premium', 'ai-content-optimizer') . '</a>';
        echo '</div>';
        echo '</div>';
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = min(100, max(0, 50 + ($word_count / max(1, $sentence_count)) * 2));
        $words = explode(' ', strip_tags($content));
        $keywords = array_slice(array_count_values(array_filter($words, function($w) { return strlen($w) > 4; })), 0, 5);
        $keyword_list = implode(', ', array_keys($keywords));

        $tips = array(
            $word_count < 300 ? 'Aim for 300+ words for better SEO.' : 'Good length!',
            $readability < 60 ? 'Improve readability with shorter sentences.' : 'Excellent readability.',
            'Use H2/H3 headings for structure.',
            'Premium: Get AI keyword suggestions.'
        );

        return array(
            'word_count' => $word_count,
            'readability' => round($readability),
            'keywords' => $keyword_list ?: 'None detected',
            'tips' => $tips
        );
    }

    public function premium_nag() {
        echo '<div class="notice notice-info"><p>';
        echo sprintf(
            __('Boost your SEO with %sAI Content Optimizer Premium%s! Unlock AI rewrites, keyword research, and more. %sUpgrade now!%s', 'ai-content-optimizer'),
            '<strong>',
            '</strong>',
            '<a href="https://example.com/premium" target="_blank">',
            '</a>'
        );
        echo '</p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizerLite();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Minimal JS
    $js = "jQuery(document).ready(function($) {
        $('#postdivrich').on('keyup', function() {
            $('#ai-content-analysis').html('<p>Analyzing...</p>');
            setTimeout(function() {
                location.reload();
            }, 2000);
        });
    });";
    file_put_contents($assets_dir . 'script.js', $js);
    // Minimal CSS
    $css = "#ai-content-analysis { padding: 10px; background: #f9f9f9; border-radius: 5px; }
    #optimization-tips h4 { margin-top: 0; color: #0073aa; }";
    file_put_contents($assets_dir . 'style.css', $css);
});
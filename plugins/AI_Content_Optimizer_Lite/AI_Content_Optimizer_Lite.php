/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better readability and SEO using simple AI heuristics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_optimizer_uses_left', 5);
    }

    public function add_admin_menu() {
        add_posts_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'uses_left' => get_option('ai_optimizer_uses_left', 5)
        ));
    }

    public function admin_page() {
        $uses_left = get_option('ai_optimizer_uses_left', 5);
        echo '<div class="wrap"><h1>AI Content Optimizer Lite</h1>';
        echo '<p>Free optimizations left this month: <strong>' . $uses_left . '</strong></p>';
        echo '<p><a href="https://example.com/premium" target="_blank" class="button button-primary">Upgrade to Premium (Unlimited)</a></p>';
        echo '<div id="optimizer-results"></div></div>';
    }

    public function handle_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $uses_left = get_option('ai_optimizer_uses_left', 5);
        if ($uses_left <= 0) {
            wp_send_json_error('Free optimizations exhausted. Upgrade to premium!');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simple AI-like heuristics for readability and SEO
        $readability_score = $this->calculate_readability($content);
        $seo_score = $this->calculate_seo_score($content);
        $suggestions = $this->generate_suggestions($content, $readability_score, $seo_score);
        $optimized_content = $this->optimize_content($content, $suggestions);

        update_option('ai_optimizer_uses_left', $uses_left - 1);

        wp_send_json_success(array(
            'readability' => $readability_score,
            'seo' => $seo_score,
            'suggestions' => $suggestions,
            'optimized_content' => $optimized_content
        ));
    }

    private function calculate_readability($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = preg_match_all('/[.!?]+/s', $content);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $score = 100 - min(50, ($avg_sentence_length - 15) * 2); // Simple Flesch-like score
        return max(0, min(100, $score));
    }

    private function calculate_seo_score($content) {
        $score = 0;
        $words = explode(' ', strip_tags($content));
        $word_count = count($words);

        // Check keyword density (assume first 5 words as potential keyword)
        $potential_keyword = implode(' ', array_slice($words, 0, 3));
        $keyword_count = 0;
        foreach ($words as $word) {
            if (stripos($word, $potential_keyword) !== false) $keyword_count++;
        }
        $density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;
        if ($density > 0.5 && $density < 2.5) $score += 30;

        // Headings
        if (preg_match_all('/<h[1-6]/', $content)) $score += 20;

        // Lists
        if (preg_match_all('/<ul|<ol/', $content)) $score += 20;

        // Images
        if (preg_match_all('/<img/', $content)) $score += 15;

        // Internal/external links
        if (preg_match_all('/<a href/', $content)) $score += 15;

        return min(100, $score);
    }

    private function generate_suggestions($content, $readability, $seo) {
        $suggestions = array();
        if ($readability < 60) $suggestions[] = 'Shorten sentences for better readability.';
        if ($seo < 70) $suggestions[] = 'Add headings (H2/H3), lists, and images with alt text.';
        $suggestions[] = 'Include 1-2% keyword density for main topic.';
        return $suggestions;
    }

    private function optimize_content($content, $suggestions) {
        // Basic optimizations: add paragraphs if needed, ensure line breaks
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        // Add a sample list if none exists
        if (!preg_match('/<ul|<ol/', $content)) {
            $content .= "\n\n<ul>\n<li>Optimized tip 1</li>\n<li>Optimized tip 2</li>\n</ul>";
        }
        return $content;
    }
}

new AIContentOptimizerLite();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    $uses_left = get_option('ai_optimizer_uses_left', 5);
    if ($uses_left <= 0) {
        echo '<div class="notice notice-warning"><p>AI Content Optimizer: Free uses exhausted. <a href="https://example.com/premium">Upgrade now</a> for unlimited access!</p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// JS file would be embedded or separate, but for single-file, include inline if needed
/* Note: In production, enqueue a separate JS file. For this single-file demo, JS is simplified via ajax. */
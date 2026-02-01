/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upgrades.
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'handle_optimize'));
        add_action('wp_ajax_aco_upgrade', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_premium_key', '');
        add_option('aco_is_premium', false);
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => get_option('aco_is_premium')
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $readability = $this->calculate_readability($content);
        $keywords = $this->extract_keywords($content);
        echo '<div id="aco-results">';
        echo '<p><strong>Readability Score:</strong> ' . $readability . '%</p>';
        echo '<p><strong>Keyword Density:</strong> ' . $keywords . '%</p>';
        echo '<button id="aco-analyze" class="button">Analyze & Optimize (Free)</button>';
        if (!get_option('aco_is_premium')) {
            echo '<p><a href="#" id="aco-upgrade" class="button button-primary">Upgrade to Premium ($4.99/mo)</a></p>';
        }
        echo '</div>';
    }

    public function calculate_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        if ($sentences == 0) return 0;
        $syl = $this->count_syllables(strip_tags($content));
        $asl = $words / $sentences;
        $asw = $syl / $words;
        $flesch = 206.835 - 1.015 * $asl - 84.6 * $asw;
        return round(max(0, min(100, 100 - ($flesch - 90) / 1.5)), 1);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]{2,}/', $text, $vowels);
        preg_match_all('/[bcdfghjklmnpqrstvwxz]+/i', $text, $consonants);
        return count($vowels) + count($consonants);
    }

    public function extract_keywords($content) {
        $words = explode(' ', strip_tags(strtolower($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        $top = reset($counts);
        return $top ? round(($top / count($words)) * 100, 1) : 0;
    }

    public function handle_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!get_option('aco_is_premium')) {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $content = sanitize_textarea_field($_POST['content']);
        // Simulate AI optimization
        $optimized = $content . '\n\n[AI Optimized: Improved SEO keywords and readability.]';
        wp_send_json_success($optimized);
    }

    public function handle_upgrade() {
        check_ajax_referer('aco_nonce', 'nonce');
        // Simulate Stripe integration (replace with real Stripe API)
        $key = sanitize_text_field($_POST['key']);
        if (strlen($key) > 10) {
            update_option('aco_premium_key', $key);
            update_option('aco_is_premium', true);
            wp_send_json_success('Upgraded to premium!');
        } else {
            wp_send_json_error('Invalid key.');
        }
    }
}

new AIContentOptimizer();

// Freemium nag
add_action('admin_notices', function() {
    if (!get_option('aco_is_premium') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock AI auto-optimization with <strong>AI Content Optimizer Pro</strong> for $4.99/mo. <a href="' . admin_url('plugins.php') . '">Upgrade now</a>!</p></div>';
    }
});
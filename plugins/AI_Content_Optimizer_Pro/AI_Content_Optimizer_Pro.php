/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_license';
    const PREMIUM_URL = 'https://example.com/premium-upgrade'; // Replace with your premium sales page

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('wp_ajax_aicop_analyze', [$this, 'handle_ajax_analyze']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('plugin_row_meta', [$this, 'add_plugin_links'], 10, 2);
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Opt.',
            'manage_options',
            'ai-content-optimizer',
            [$this, 'settings_page']
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'aicop_analysis',
            'AI Content Optimizer',
            [$this, 'meta_box_content'],
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_content($post) {
        wp_nonce_field('aicop_analyze_post', 'aicop_nonce');
        $post_id = $post->ID;
        echo '<div id="aicop-results-' . $post_id . '"></div>';
        echo '<button type="button" class="button aicop-analyze-btn" data-post-id="' . $post_id . '">Analyze Content</button>';
        echo '<p class="description">Free: Basic SEO score. <a href="' . self::PREMIUM_URL . '" target="_blank">Premium: AI Rewrite & More</a></p>';
    }

    public function settings_page() {
        if (isset($_POST['aicop_submit'])) {
            update_option('aicop_api_key', sanitize_text_field($_POST['api_key']));
        }
        $api_key = get_option('aicop_api_key', '');
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post"><table class="form-table">';
        echo '<tr><th>API Key (Premium)</th><td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td></tr>';
        echo '</table><p class="submit"><input type="submit" name="aicop_submit" class="button-primary" value="Save"></p></form>';
        echo '<p><strong>Upgrade to Premium</strong> for AI-powered rewriting, bulk processing, and advanced metrics. <a href="' . self::PREMIUM_URL . '" target="_blank">Get Premium</a></p>';
        echo '</div>';
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php' || $hook === 'settings_page_ai-content-optimizer') {
            wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], '1.0.0', true);
            wp_localize_script('aicop-admin', 'aicop_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aicop_ajax_nonce')
            ]);
        }
    }

    public function handle_ajax_analyze() {
        check_ajax_referer('aicop_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, (50 + ($readability / 2) + (min(500, $word_count) / 10)));

        $is_premium = $this->is_premium();
        $results = [
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'seo_score' => round($seo_score),
            'is_premium' => $is_premium,
            'premium_msg' => $is_premium ? 'Premium: AI Rewrite available!' : 'Upgrade for AI Rewrite'
        ];

        if ($is_premium && isset($_POST['full_rewrite'])) {
            // Simulate AI rewrite (in real premium, integrate OpenAI API)
            $results['rewrite'] = $this->mock_ai_rewrite($content);
        }

        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text) ?: 1;
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = '/(?!e[\\d])[aeiouy]+/i';
        return preg_match_all($rules, $text);
    }

    private function mock_ai_rewrite($content) {
        // Mock premium AI rewrite - replace with real API in premium version
        return 'Premium AI Rewrite: ' . substr($content, 0, 200) . '... (Optimized for SEO and engagement!)';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'valid';
    }

    public function add_plugin_links($links, $file) {
        if ($file === plugin_basename(__FILE__)) {
            $links[] = '<a href="' . self::PREMIUM_URL . '" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/support">Support</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function aicop_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'edit-post') {
        echo '<div class="notice notice-info"><p>Enhance your content with <strong>AI Content Optimizer Pro</strong>! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Settings</a> | <a href="https://example.com/premium-upgrade" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aicop_admin_notice');

// Prevent direct access to PHP file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    wp_die('Access denied.');
}
?>
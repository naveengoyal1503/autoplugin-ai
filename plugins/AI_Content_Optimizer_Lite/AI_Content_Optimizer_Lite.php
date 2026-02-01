/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Premium version unlocks advanced AI features.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    const VERSION = '1.0.0';
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_html'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results">';
        echo '<button type="button" id="aco-analyze" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
        echo '<div id="aco-loading" style="display:none;">Analyzing...</div>';
        echo '<div id="aco-output"></div>';
        echo '<p><small><strong>Free:</strong> Basic analysis (3/day). <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Go Premium</a> for unlimited AI suggestions & more!</small></p>';
        echo '</div>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate daily limit (store in user meta)
        $user_id = get_current_user_id();
        $today = date('Y-m-d');
        $usage = get_user_meta($user_id, 'aco_usage_' . $today, true) ?: 0;
        if ($usage >= 3) {
            wp_die(json_encode(array('error' => 'Daily limit reached. Upgrade to premium!')));
        }
        update_user_meta($user_id, 'aco_usage_' . $today, $usage + 1);

        // Basic analysis (simulated AI - in premium, integrate real API like OpenAI)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 / $word_count * $sentence_count, 1) : 0; // Flesch-like score
        $keywords = $this->extract_keywords($content, 5);
        $seo_score = min(100, ($word_count / 500 * 30) + (count($keywords) * 10) + ($readability / 2));

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'keywords' => $keywords,
            'seo_score' => round($seo_score),
            'tips' => array(
                'Use more subheadings if score < 70',
                'Aim for 8th-grade readability',
                '<a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Premium: AI rewrites & meta optimization</a>'
            )
        );

        wp_die(json_encode($results));
    }

    private function extract_keywords($content, $limit) {
        $words = preg_split('/\s+/', strtolower(strip_tags($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_slice(array_keys($counts), 0, $limit);
    }

    public function activate() {
        // Activation hook
    }
}

new AIContentOptimizerLite();

// Freemium upsell notice
function aco_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Content Optimizer Lite:</strong> Unlock unlimited analyses, AI suggestions, and SEO auto-fixes with <a href="https://example.com/premium-upgrade" target="_blank">Premium</a> for just $4.99/mo!</p></div>';
}
add_action('admin_notices', 'aco_admin_notice');

// Prevent direct access
if (!defined('ABSPATH')) exit;
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using AI-powered insights.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-performance',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ('toplevel_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), self::VERSION, true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium(),
            'usage_limit' => $this->get_usage_limit(),
            'remaining' => $this->get_remaining_usage()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), self::VERSION);
    }

    public function admin_page() {
        $is_premium = $this->is_premium();
        $remaining = $this->get_remaining_usage();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium() && $this->get_remaining_usage() <= 0) {
            wp_send_json_error('Usage limit exceeded. Upgrade to premium.');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $suggestions = $this->analyze_content($content);

        if (!$this->is_premium()) {
            $this->decrement_usage();
        }

        wp_send_json_success($suggestions);
    }

    private function analyze_content($content) {
        // Basic keyword density, readability simulation
        $word_count = str_word_count(strip_tags($content));
        $keywords = $this->extract_keywords($content);
        $readability = $this->calculate_readability($content);

        $suggestions = array(
            'word_count' => $word_count,
            'keywords' => $keywords,
            'readability_score' => $readability,
            'improvements' => array(
                'Add more headings for better structure.',
                'Include ' . $keywords . ' in the first paragraph.',
                'Aim for 1.5-2.5% keyword density.'
            ),
            'optimized_snippet' => $this->generate_optimized_snippet($content)
        );

        if ($this->is_premium()) {
            $suggestions['advanced'] = array(
                'meta_title_suggestion' => 'Optimized Title with Primary Keyword',
                'meta_desc_suggestion' => substr(strip_tags($content), 0, 155) . '...'
            );
        }

        return $suggestions;
    }

    private function extract_keywords($content) {
        // Simple keyword extraction simulation
        return array('wordpress', 'seo', 'content');
    }

    private function calculate_readability($content) {
        return rand(60, 90); // Simulated Flesch score
    }

    private function generate_optimized_snippet($content) {
        return '<p>Optimized intro with **key phrases** and SEO best practices applied.</p>';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    private function get_usage_limit() {
        return $this->is_premium() ? 999 : 5;
    }

    private function get_remaining_usage() {
        $used = get_option('ai_optimizer_usage', 0);
        return max(0, $this->get_usage_limit() - $used);
    }

    private function decrement_usage() {
        $used = get_option('ai_optimizer_usage', 0) + 1;
        update_option('ai_optimizer_usage', $used);
    }

    public function activate() {
        if (!$this->is_premium()) {
            update_option('ai_optimizer_usage', 0);
        }
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ('toplevel_page_ai-content-optimizer' === $screen->id) return;
    if (get_option('ai_optimizer_usage') >= 5) {
        echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations with <strong>AI Content Optimizer Pro</strong>! <a href="' . admin_url('admin.php?page=ai-content-optimizer') . '">Upgrade now</a>.</p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

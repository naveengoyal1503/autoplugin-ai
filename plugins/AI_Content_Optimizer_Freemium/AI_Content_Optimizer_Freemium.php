/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability with AI-powered suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_nopriv_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_usage_count', 0);
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), self::VERSION, true);
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), self::VERSION, true);
        wp_localize_script('ai-optimizer-admin', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium(),
            'usage_limit' => 5,
            'current_usage' => get_option('ai_content_optimizer_usage_count', 0)
        ));
    }

    public function settings_page() {
        $is_premium = $this->is_premium();
        $usage_count = get_option('ai_content_optimizer_usage_count', 0);
        include plugin_dir_path(__FILE__) . 'templates/settings.php';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium()) {
            $usage_count = get_option('ai_content_optimizer_usage_count', 0);
            if ($usage_count >= 5) {
                wp_send_json_error('Free limit reached. Upgrade to premium for unlimited access.');
            }
            update_option('ai_content_optimizer_usage_count', $usage_count + 1);
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $suggestions = $this->analyze_content($content);

        wp_send_json_success($suggestions);
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $readability_score = min(100, 50 + ($word_count / 100)); // Simulated
        $seo_score = min(100, 60 + (substr_count(strtolower($content), 'keyword') * 5)); // Simulated

        $suggestions = array(
            'readability_score' => $readability_score,
            'seo_score' => $seo_score,
            'tips' => array(
                'Add more subheadings for better structure.',
                'Include keywords naturally.',
                'Shorten sentences for readability.'
            ),
            'optimized_content' => $this->get_optimized_content($content)
        );

        if ($this->is_premium()) {
            $suggestions['premium_tips'] = array('AI-generated meta description', 'Keyword density optimization');
        }

        return $suggestions;
    }

    private function get_optimized_content($content) {
        // Basic optimization simulation
        return '<h2>Optimized Heading</h2><p>' . substr($content, 0, 200) . '...</p>';
    }

    private function is_premium() {
        $premium_key = get_option(self::PREMIUM_KEY);
        return !empty($premium_key) && hash('sha256', $premium_key) === 'premium_verified_hash'; // Simulated verification
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $usage_count = get_option('ai_content_optimizer_usage_count', 0);
    if ($usage_count >= 5) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Content Optimizer Premium</strong> for unlimited optimizations! <a href="https://example.com/premium">Get Premium</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

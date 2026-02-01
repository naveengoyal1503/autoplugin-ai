/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyze and optimize your WordPress content for better SEO with AI suggestions. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $usage = get_option('aco_usage_count', 0);
        $limit = 5; // Free limit
        $is_premium = get_option('aco_premium_active', false);
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<p>Free usage this month: ' . $usage . '/' . $limit . '</p>';
        if (!$is_premium && $usage >= $limit) {
            echo '<p><strong>Upgrade to premium for unlimited optimizations!</strong> <a href="https://example.com/premium" target="_blank">Get Premium</a></p>';
        }
        echo '<textarea id="aco-content" rows="20" cols="100" placeholder="Paste your content here..."></textarea>';
        echo '<button id="aco-optimize-btn" class="button button-primary">Optimize Content</button>';
        echo '<div id="aco-result"></div>';
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $usage = get_option('aco_usage_count', 0);
        $limit = 5;
        $is_premium = get_option('aco_premium_active', false);

        if (!$is_premium && $usage >= $limit) {
            wp_send_json_error('Free limit reached. Upgrade to premium!');
        }

        // Simulate AI optimization (in real version, integrate OpenAI API or similar)
        $optimized = $this->mock_ai_optimize($content);

        if (!$is_premium) {
            update_option('aco_usage_count', $usage + 1);
        }

        wp_send_json_success(array('optimized' => $optimized));
    }

    private function mock_ai_optimize($content) {
        // Mock optimization: Add keywords, improve structure (premium would use real AI)
        $suggestions = array(
            'Added SEO keywords: WordPress, plugin, optimization.',
            'Improved readability with shorter sentences.',
            'Suggested meta title: "Optimize Your Content with AI"',
            'Premium: Full AI rewrite available.'
        );
        return $content . '\n\n<h3>AI Suggestions:</h3><ul>' . implode('', array_map(function($s) { return '<li>' . $s . '</li>'; }, $suggestions)) . '</ul>';
    }

    public function activate() {
        add_option('aco_usage_count', 0);
        add_option('aco_premium_active', false);
    }
}

new AIContentOptimizer();

// Premium nag
add_action('admin_notices', function() {
    $usage = get_option('aco_usage_count', 0);
    if ($usage >= 5 && !get_option('aco_premium_active', false)) {
        echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations with <a href="https://example.com/premium" target="_blank">AI Content Optimizer Premium</a>!</p></div>';
    }
});

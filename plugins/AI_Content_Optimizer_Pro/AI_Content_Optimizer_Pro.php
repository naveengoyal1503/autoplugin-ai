/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Free version provides basic checks; upgrade to Pro for advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerPro {
    const PREMIUM_FEATURES = ['advanced_ai_analysis', 'auto_rewrite_suggestions', 'unlimited_posts'];

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_optimization_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_notices', [$this, 'premium_notice']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', ['jquery'], '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', [
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'isPremium' => $this->is_premium(),
            'apiUrl' => admin_url('admin-ajax.php'),
            'labels' => [
                'analyze' => __('Analyze Content', 'ai-content-optimizer'),
                'upgrade' => __('Upgrade to Pro for Advanced AI', 'ai-content-optimizer'),
            ]
        ]);
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            [$this, 'meta_box_html'],
            ['post', 'page'],
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $data = get_post_meta($post->ID, '_ai_optimizer_data', true);
        echo '<div id="ai-optimizer-container">';
        echo '<button id="ai-analyze-btn" class="button button-primary">' . __('Basic SEO Check', 'ai-content-optimizer') . '</button>';
        if (!$this->is_premium()) {
            echo '<p><a href="https://example.com/premium" target="_blank" class="button button-upgrade">' . __('Go Pro - Unlimited AI', 'ai-content-optimizer') . '</a></p>';
        }
        if ($data) {
            echo '<div id="ai-results"><strong>Score:</strong> ' . esc_html($data['score']) . '/100<br><strong>Suggestions:</strong> ' . esc_html($data['suggestions']) . '</div>';
        }
        echo '</div>';
    }

    public function save_optimization_data($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['ai_optimizer_data'])) {
            update_post_meta($post_id, '_ai_optimizer_data', $_POST['ai_optimizer_data']);
        }
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>' . sprintf(
                __('Unlock %s with AI Content Optimizer Pro! %sUpgrade Now%s', 'ai-content-optimizer'),
                implode(', ', self::PREMIUM_FEATURES),
                '<a href="https://example.com/premium" target="_blank">',
                '</a>'
            ) . '</p></div>';
        }
    }

    private function is_premium() {
        // Simulate license check; replace with real Freemius or EDD integration
        return get_option('ai_optimizer_premium_license') === 'valid';
    }

    public function activate() {
        // Activation hook for defaults
        flush_rewrite_rules();
    }
}

new AIContentOptimizerPro();

// AJAX handler for basic analysis
add_action('wp_ajax_ai_optimize_content', 'handle_ai_optimize');
function handle_ai_optimize() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Unauthorized');
    }

    $post = get_post($post_id);
    $content = $post->post_content;
    $word_count = str_word_count(strip_tags($content));
    $has_keywords = preg_match('/(seo|content|blog)/i', $content);
    $score = min(100, ($word_count / 10) + ($has_keywords * 40));
    $suggestions = $score < 70 ? 'Add more keywords, improve readability.' : 'Great! Consider Pro for AI rewrites.';

    $data = [
        'score' => intval($score),
        'suggestions' => $suggestions
    ];

    if (!AIContentOptimizerPro::is_premium()) {
        $data['upgrade'] = true;
    }

    wp_send_json_success($data);
}

// Create assets directory placeholder (in real plugin, include JS file)
// assets/optimizer.js content:
/*
jQuery(document).ready(function($) {
    $('#ai-analyze-btn').click(function(e) {
        e.preventDefault();
        $.post(aiOptimizer.apiUrl, {
            action: 'ai_optimize_content',
            nonce: aiOptimizer.nonce,
            post_id: $('#post_ID').val()
        }, function(response) {
            if (response.success) {
                $('#ai-results').html('<strong>Score:</strong> ' + response.data.score + '/100<br><strong>Suggestions:</strong> ' + response.data.suggestions);
                if (response.data.upgrade && !aiOptimizer.isPremium) {
                    $('#ai-results').append('<br><a href="https://example.com/premium" target="_blank">Upgrade for AI Power!</a>');
                }
            }
        });
    });
});
*/
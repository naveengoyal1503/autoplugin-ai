/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO.
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

// Define plugin constants
define('AICONTENTOPTIMIZER_VERSION', '1.0.0');
define('AICONTENTOPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICONTENTOPTIMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Freemium check - Simulate license (in real, integrate with Freemius or similar)
function aicontentoptimizer_is_premium() {
    // For demo, check for a transient or option. In production, verify license key.
    return get_option('aicontentoptimizer_premium_active', false);
}

function aicontentoptimizer_free_limit_reached() {
    $today = date('Y-m-d');
    $count = get_transient('aicontentoptimizer_free_count') ?: 0;
    if ($count >= 5) {
        return true;
    }
    set_transient('aicontentoptimizer_free_count', $count + 1, DAY_IN_SECONDS);
    return false;
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'aicontentoptimizer_admin_scripts');
function aicontentoptimizer_admin_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    wp_enqueue_script('aicontentoptimizer-admin', AICONTENTOPTIMIZER_PLUGIN_URL . 'admin.js', ['jquery'], AICONTENTOPTIMIZER_VERSION, true);
    wp_enqueue_style('aicontentoptimizer-admin', AICONTENTOPTIMIZER_PLUGIN_URL . 'admin.css', [], AICONTENTOPTIMIZER_VERSION);
}

// Add meta box to post editor
add_action('add_meta_boxes', 'aicontentoptimizer_add_meta_box');
function aicontentoptimizer_add_meta_box() {
    add_meta_box('aicontentoptimizer-box', 'AI Content Optimizer', 'aicontentoptimizer_meta_box_callback', ['post', 'page'], 'side', 'high');
}

function aicontentoptimizer_meta_box_callback($post) {
    wp_nonce_field('aicontentoptimizer_nonce', 'aicontentoptimizer_nonce');
    echo '<div id="ai-content-optimizer-results"></div>';
    echo '<button type="button" id="ai-optimize-content" class="button button-primary">Analyze & Optimize</button>';
    if (!aicontentoptimizer_is_premium()) {
        echo '<p><strong>Premium:</strong> Unlimited analyzes, AI rewrites & more. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_optimize_content', 'aicontentoptimizer_ajax_optimize');
function aicontentoptimizer_ajax_optimize() {
    check_ajax_referer('aicontentoptimizer_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }

    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    if (!aicontentoptimizer_is_premium() && aicontentoptimizer_free_limit_reached()) {
        wp_send_json_error('Free limit reached. Upgrade to premium for unlimited access.');
    }

    // Simulate AI analysis (in production, integrate OpenAI API or similar)
    $suggestions = aicontentoptimizer_simulate_ai_analysis($content);

    if (aicontentoptimizer_is_premium()) {
        $suggestions['rewrite'] = aicontentoptimizer_simulate_ai_rewrite($content);
    }

    wp_send_json_success($suggestions);
}

function aicontentoptimizer_simulate_ai_analysis($content) {
    $word_count = str_word_count(strip_tags($content));
    $readability = rand(60, 90);
    return [
        'word_count' => $word_count,
        'readability' => $readability . '%',
        'seo_score' => rand(70, 100) . '/100',
        'suggestions' => [
            'Add more headings for structure.',
            'Include 2-3 target keywords naturally.',
            'Shorten sentences for better readability.'
        ],
        'premium_only' => !aicontentoptimizer_is_premium()
    ];
}

function aicontentoptimizer_simulate_ai_rewrite($content) {
    // Truncate for demo
    return substr($content, 0, 200) . '... (Premium AI Rewrite)';
}

// Admin page for settings and upgrade
add_action('admin_menu', 'aicontentoptimizer_admin_menu');
function aicontentoptimizer_admin_menu() {
    add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', 'aicontentoptimizer_settings_page');
}

function aicontentoptimizer_settings_page() {
    if (isset($_POST['premium_key'])) {
        update_option('aicontentoptimizer_premium_active', true);
        echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Settings</h1>
        <?php if (!aicontentoptimizer_is_premium()): ?>
        <form method="post">
            <p>Enter premium key: <input type="text" name="premium_key" placeholder="Premium License Key"></p>
            <p><input type="submit" class="button-primary" value="Activate Premium"></p>
        </form>
        <p><a href="https://example.com/premium" target="_blank" class="button button-primary">Buy Premium ($9/mo)</a></p>
        <?php else: ?>
        <p>✅ Premium active. Enjoy unlimited features!</p>
        <?php endif; ?>
    </div>
    <?php
}

// Plugin activation hook
register_activation_hook(__FILE__, 'aicontentoptimizer_activate');
function aicontentoptimizer_activate() {
    set_transient('aicontentoptimizer_free_count', 0, DAY_IN_SECONDS);
}

// Prevent direct access to JS/CSS (create empty files in production)
// admin.js content: jQuery(document).ready(function($){ $('#ai-optimize-content').click(function(){ var data = { action: 'ai_optimize_content', post_id: $('#post_ID').val(), nonce: $('#aicontentoptimizer_nonce').val() }; $.post(ajaxurl, data, function(resp){ $('#ai-content-optimizer-results').html('<pre>' + JSON.stringify(resp.data, null, 2) + '</pre>'); }); }); });
// admin.css content: #ai-content-optimizer-results { background: #f9f9f9; padding: 10px; max-height: 300px; overflow-y: scroll; }
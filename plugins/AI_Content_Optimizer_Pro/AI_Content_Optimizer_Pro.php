/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content with AI-powered suggestions for SEO, readability, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('AICOP_VERSION', '1.0.0');
define('AICOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICOP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Premium check (simulate license - in real, integrate with your API)
function aicop_is_premium() {
    return get_option('aicop_premium_active', false);
}

function aicop_enqueue_assets() {
    wp_enqueue_style('aicop-admin-css', AICOP_PLUGIN_URL . 'assets/style.css', array(), AICOP_VERSION);
    wp_enqueue_script('aicop-admin-js', AICOP_PLUGIN_URL . 'assets/script.js', array('jquery'), AICOP_VERSION, true);
}

// Add meta box to post editor
add_action('add_meta_boxes', 'aicop_add_meta_box');
function aicop_add_meta_box() {
    add_meta_box('aicop-optimizer', 'AI Content Optimizer', 'aicop_meta_box_callback', 'post', 'side', 'high');
    add_meta_box('aicop-optimizer', 'AI Content Optimizer', 'aicop_meta_box_callback', 'page', 'side', 'high');
}

function aicop_meta_box_callback($post) {
    wp_nonce_field('aicop_optimize_nonce', 'aicop_nonce');
    $content = get_post_field('post_content', $post->ID);
    $usage = get_option('aicop_usage_count', 0);
    $limit = aicop_is_premium() ? 999 : 5;
    echo '<div id="aicop-results"></div>';
    echo '<p><strong>Daily Limit:</strong> ' . $usage . '/' . $limit . '</p>';
    echo '<button id="aicop-optimize-btn" class="button button-primary" data-post-id="' . $post->ID . '">Optimize Content</button>';
    echo '<p id="aicop-status"></p>';
}

// AJAX handler for optimization
add_action('wp_ajax_aicop_optimize', 'aicop_handle_optimize');
function aicop_handle_optimize() {
    check_ajax_referer('aicop_optimize_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }

    $post_id = intval($_POST['post_id']);
    $usage = get_option('aicop_usage_count', 0);
    $limit = aicop_is_premium() ? 999 : 5;

    if ($usage >= $limit) {
        wp_send_json_error('Daily limit reached. Upgrade to premium for unlimited access.');
    }

    $post = get_post($post_id);
    $content = $post->post_content;

    // Simulate AI optimization (in real plugin, integrate OpenAI API or similar)
    $suggestions = aicop_generate_suggestions($content);

    update_option('aicop_usage_count', $usage + 1);

    wp_send_json_success($suggestions);
}

function aicop_generate_suggestions($content) {
    // Mock AI suggestions - replace with real AI API call
    $word_count = str_word_count(strip_tags($content));
    $seo_score = rand(60, 95);
    $readability = rand(70, 90);

    return array(
        'seo_score' => $seo_score,
        'readability' => $readability,
        'word_count' => $word_count,
        'tips' => array(
            'Add keywords: ' . ucfirst(get_the_title()),
            'Improve headings: Use H2/H3 tags.',
            'Enhance readability: Shorten sentences.',
            'Premium: Auto-rewrite paragraphs (upgrade required).'
        ),
        'optimized_snippet' => substr(wp_trim_words($content, 55), 0, 200) . '...'
    );
}

// Admin menu for settings
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'aicop-settings', 'aicop_settings_page');
}

function aicop_settings_page() {
    if (isset($_POST['aicop_premium_key'])) {
        // Simulate activation
        update_option('aicop_premium_active', true);
        echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Settings</h1>
        <form method="post">
            <?php if (!aicop_is_premium()) : ?>
                <p>Enter premium key or <a href="https://example.com/premium" target="_blank">buy now ($9.99/mo)</a></p>
                <input type="text" name="aicop_premium_key" placeholder="Premium License Key" />
                <input type="submit" class="button-primary" value="Activate Premium" />
            <?php else : ?>
                <p>✅ Premium active. Unlimited optimizations unlocked!</p>
            <?php endif; ?>
        </form>
    </div>
    <?php
}

// Reset daily usage (cron)
add_action('aicop_reset_usage', 'aicop_reset_daily_usage');
function aicop_reset_daily_usage() {
    update_option('aicop_usage_count', 0);
}

// Schedule cron
register_activation_hook(__FILE__, 'aicop_schedule_cron');
register_deactivation_hook(__FILE__, 'aicop_unschedule_cron');

function aicop_schedule_cron() {
    if (!wp_next_scheduled('aicop_reset_usage')) {
        wp_schedule_event(strtotime('tomorrow 00:00:00'), 'daily', 'aicop_reset_usage');
    }
}

function aicop_unschedule_cron() {
    wp_clear_scheduled_hook('aicop_reset_usage');
}

// Enqueue dummy assets (create empty files in real deployment)
add_action('admin_enqueue_scripts', 'aicop_enqueue_assets');

// Freemium upsell notice
add_action('admin_notices', 'aicop_upsell_notice');
function aicop_upsell_notice() {
    if (!aicop_is_premium() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>AI Content Optimizer: Unlock unlimited optimizations and advanced AI rewrites with <a href="' . admin_url('options-general.php?page=aicop-settings') . '">Premium</a> for just $9.99/month!</p></div>';
    }
}

?>
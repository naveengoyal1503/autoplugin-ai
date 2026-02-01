/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO using AI-powered suggestions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AICOP_VERSION', '1.0.0');
define('AICOP_PATH', plugin_dir_path(__FILE__));
define('AICOP_PREMIUM', get_option('aicop_premium_key') !== false);

// Premium check function
function aicop_is_premium() {
    return AICOP_PREMIUM;
}

// Admin menu
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_management_page(
        'AI Content Optimizer',
        'AI Content Optimizer',
        'manage_options',
        'aicop',
        'aicop_admin_page'
    );
}

// Admin page
function aicop_admin_page() {
    if (!current_user_can('manage_options')) return;

    $posts_analyzed = get_option('aicop_posts_analyzed', 0);
    $limit = aicop_is_premium() ? 'Unlimited' : '5 per month';

    echo '<div class="wrap">';
    echo '<h1>AI Content Optimizer Pro</h1>';
    echo '<p><strong>Posts analyzed this month:</strong> ' . $posts_analyzed . ' / ' . $limit . '</p>';

    if (!aicop_is_premium()) {
        echo '<div class="notice notice-warning"><p>Upgrade to Premium for unlimited optimizations and advanced features! <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
    }

    echo '<form method="post">';
    echo '<p><label for="post_id">Select Post:</label> ';
    wp_dropdown_posts(array('post_type' => 'post', 'numberposts' => -1, 'name' => 'post_id'));
    echo ' <input type="submit" name="aicop_analyze" class="button button-primary" value="Analyze & Optimize"></p>';
    echo '</form>';

    if (isset($_POST['aicop_analyze']) && isset($_POST['post_id'])) {
        aicop_analyze_post(absint($_POST['post_id']));
    }

    echo '</div>';
}

// Analyze post
function aicop_analyze_post($post_id) {
    if (!current_user_can('edit_post', $post_id)) return;

    if (!aicop_check_limit()) {
        echo '<div class="notice notice-error"><p>Free limit reached. Upgrade to Premium!</p></div>';
        return;
    }

    $post = get_post($post_id);
    $content = $post->post_content;

    // Simulate AI analysis (in real version, integrate OpenAI API or similar)
    $suggestions = aicop_generate_suggestions($content);

    // Display results
    echo '<h2>Analysis Results for "' . esc_html($post->post_title) . '"</h2>';
    echo '<h3>SEO Score: ' . aicop_calculate_score($content) . '/100</h3>';
    echo '<h3>Suggestions:</h3><ul>';
    foreach ($suggestions as $suggestion) {
        echo '<li>' . esc_html($suggestion) . '</li>';
    }
    echo '</ul>';

    if (aicop_is_premium()) {
        echo '<p><a href="#" onclick="aicopAutoOptimize(' . $post_id . ')" class="button button-secondary">Auto-Optimize (Premium)</a></p>';
    }

    aicop_update_usage();
}

// Check free limit
function aicop_check_limit() {
    if (aicop_is_premium()) return true;
    $analyzed = get_option('aicop_posts_analyzed', 0);
    return $analyzed < 5;
}

// Update usage
function aicop_update_usage() {
    if (!aicop_is_premium()) {
        $count = get_option('aicop_posts_analyzed', 0) + 1;
        update_option('aicop_posts_analyzed', $count);
    }
}

// Generate mock AI suggestions
function aicop_generate_suggestions($content) {
    $suggestions = array(
        'Add more keywords related to your main topic.',
        'Improve readability by shortening sentences.',
        'Include internal links to related posts.',
        'Optimize meta description for better click-through.'
    );
    if (aicop_is_premium()) {
        $suggestions[] = 'Premium: Advanced keyword density optimization suggested.';
    }
    return $suggestions;
}

// Calculate mock SEO score
function aicop_calculate_score($content) {
    $score = 65 + (rand(0, 30)); // Mock calculation
    if (aicop_is_premium()) $score += 10;
    return min(100, $score);
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'aicop_scripts');
function aicop_scripts($hook) {
    if ($hook !== 'tools_page_aicop') return;
    wp_enqueue_script('aicop-js', plugin_dir_url(__FILE__) . 'aicop.js', array('jquery'), AICOP_VERSION);
}

// Premium activation (simplified)
register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    if (isset($_POST['aicop_premium_key'])) {
        update_option('aicop_premium_key', sanitize_text_field($_POST['aicop_premium_key']));
    }
}

// Reset monthly count (cron or manual)
add_action('aicop_monthly_reset', 'aicop_reset_monthly');
function aicop_reset_monthly() {
    delete_option('aicop_posts_analyzed');
}

// Mock JS for auto-optimize
/* In real plugin, add aicop.js file with premium features */
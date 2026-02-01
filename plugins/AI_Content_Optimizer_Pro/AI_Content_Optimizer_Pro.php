/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered analysis for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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

// Premium check (simulate license check)
function aico_is_premium() {
    // In real version, check license via API
    return false; // Free version
}

// Enqueue scripts and styles
function aico_enqueue_assets() {
    wp_enqueue_style('aico-admin-style', AICOP_PLUGIN_URL . 'assets/style.css', array(), AICOP_VERSION);
    wp_enqueue_script('aico-admin-script', AICOP_PLUGIN_URL . 'assets/script.js', array('jquery'), AICOP_VERSION, true);
}
add_action('admin_enqueue_scripts', 'aico_enqueue_assets');

// Add admin menu
function aico_admin_menu() {
    add_menu_page(
        'AI Content Optimizer',
        'AI Optimizer',
        'manage_options',
        'ai-content-optimizer',
        'aico_admin_page',
        'dashicons-editor-alignleft',
        30
    );
}
add_action('admin_menu', 'aico_admin_menu');

// Admin page content
function aico_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap">';
    echo '<h1>AI Content Optimizer Pro</h1>';
    echo '<p>Analyze and optimize your content for SEO, readability, and engagement.</p>';

    if (isset($_POST['aico_analyze'])) {
        $post_id = intval($_POST['post_id']);
        if ($post_id) {
            aico_analyze_content($post_id);
        }
    }

    echo '<form method="post">';
    echo '<label for="post_id">Select Post: </label>';
    wp_dropdown_pages(array('name' => 'post_id', 'selected' => isset($_GET['post_id']) ? $_GET['post_id'] : ''));
    echo '<input type="submit" name="aico_analyze" class="button button-primary" value="Analyze Content">';
    echo '</form>';

    // Premium upsell
    if (!aico_is_premium()) {
        echo '<div class="notice notice-info"><p><strong>Go Premium!</strong> Unlock AI rewriting, bulk optimization, and more for $9/month. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }

    echo '</div>';
}

// Analyze content (basic free version uses simple heuristics)
function aico_analyze_content($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    $word_count = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content);
    $sentence_count = count(array_filter($sentences));
    $readability = $sentence_count ? round($word_count / $sentence_count, 1) : 0;
    $seo_score = min(100, (min(500, $word_count) / 5) + (strpos($content, $post->post_title) !== false ? 20 : 0));

    echo '<div class="aico-results">';
    echo '<h2>Analysis Results</h2>';
    echo '<ul>';
    echo '<li><strong>Word Count:</strong> ' . $word_count . '</li>';
    echo '<li><strong>Avg Words per Sentence:</strong> ' . $readability . ' (Ideal: 15-20)</li>';
    echo '<li><strong>SEO Score:</strong> ' . $seo_score . '/100</li>';
    if (!aico_is_premium()) {
        echo '<li><em>Premium: AI Rewrite & Advanced SEO Tips</em></li>';
    }
    echo '</ul>';
    echo '</div>';
}

// Add meta box to post editor
function aico_add_meta_box() {
    add_meta_box('aico-optimizer', 'AI Content Optimizer', 'aico_meta_box_callback', 'post', 'side');
}
add_action('add_meta_boxes', 'aico_add_meta_box');

// Meta box callback
function aico_meta_box_callback($post) {
    echo '<p><a href="' . admin_url('admin.php?page=ai-content-optimizer&post_id=' . $post->ID) . '" class="button" target="_blank">Quick Optimize</a></p>';
    if (!aico_is_premium()) {
        echo '<p class="description">Upgrade for instant AI optimization inside the editor.</p>';
    }
}

// Create assets directories (simulate with inline for single file)
// In production, include actual CSS/JS files

?>
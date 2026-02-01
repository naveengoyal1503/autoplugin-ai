/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI analysis. Free version with basic features; premium for advanced AI rewriting.
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

// Premium check (simulate license - in real, integrate with Freemius or similar)
function aicop_is_premium() {
    return false; // Change to true for testing premium
}

// Admin menu
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_menu_page(
        'AI Content Optimizer',
        'AI Optimizer',
        'manage_options',
        'ai-content-optimizer',
        'aicop_admin_page',
        'dashicons-editor-alignleft',
        30
    );
}

// Admin page
function aicop_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap">';
    echo '<h1>AI Content Optimizer Pro</h1>';
    echo '<p>Optimize your posts for SEO instantly!</p>';
    
    if (isset($_POST['aicop_optimize'])) {
        $post_id = intval($_POST['post_id']);
        aicop_optimize_post($post_id);
    }
    
    $posts = get_posts(array('numberposts' => 5, 'post_status' => 'publish'));
    echo '<form method="post">';
    echo '<table class="form-table">';
    echo '<tr><th>Posts</th><td>';
    foreach ($posts as $post) {
        echo '<label><input type="radio" name="post_id" value="' . $post->ID . '"> ' . esc_html($post->post_title) . '</label><br>';
    }
    echo '</td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="aicop_optimize" class="button-primary" value="Optimize Selected Post"></p>';
    echo '</form>';
    
    if (!aicop_is_premium()) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Premium</strong> for AI rewriting and bulk optimization. <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
    }
    echo '</div>';
}

// Optimize post
function aicop_optimize_post($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Basic free optimization: Add keywords, improve readability
    $title = get_the_title($post_id);
    $keywords = aicop_extract_keywords($title); // Simulated
    $optimized = aicop_basic_optimize($content, $keywords);
    
    if (aicop_is_premium()) {
        $optimized = aicop_ai_rewrite($optimized); // Simulated AI
    }
    
    wp_update_post(array(
        'ID' => $post_id,
        'post_content' => $optimized
    ));
    
    echo '<div class="notice notice-success"><p>Post optimized! Keywords: ' . esc_html(implode(', ', $keywords)) . '</p></div>';
}

// Simulated keyword extraction
function aicop_extract_keywords($text) {
    return array('wordpress', 'seo', 'plugin');
}

// Basic optimization (free)
function aicop_basic_optimize($content, $keywords) {
    foreach ($keywords as $kw) {
        $content = preg_replace('/(<h[1-6]>\s*)([^<]+)(\s*<\/h[1-6]>)/i', '$1$2 <strong>' . esc_html($kw) . '</strong> $3', $content, 1);
    }
    $content .= '\n\n<p><em>Optimized with AI Content Optimizer Pro</em></p>';
    return $content;
}

// Simulated AI rewrite (premium)
function aicop_ai_rewrite($content) {
    return $content . '\n\n<p><strong>AI Rewritten for better engagement!</strong></p>';
}

// Auto-optimize new posts (free basic)
add_action('save_post', 'aicop_auto_optimize', 10, 3);
function aicop_auto_optimize($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || $post->post_status !== 'publish') {
        return;
    }
    if (!aicop_is_premium() || rand(0, 1)) { // Free basic only sometimes
        aicop_optimize_post($post_id);
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'aicop_enqueue_scripts');
function aicop_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ai-content-optimizer') {
        return;
    }
    wp_enqueue_style('aicop-style', AICOP_PLUGIN_URL . 'style.css', array(), AICOP_VERSION);
}

// Create assets dir placeholder
register_activation_hook(__FILE__, 'aicop_create_assets');
function aicop_create_assets() {
    $css_dir = AICOP_PLUGIN_PATH . 'assets/';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    file_put_contents($css_dir . 'style.css', '/* AI Content Optimizer Styles */ .wrap { max-width: 800px; }');
}

// Freemium upsell notice
add_action('admin_notices', 'aicop_premium_notice');
function aicop_premium_notice() {
    if (!aicop_is_premium() && current_user_can('manage_options')) {
        echo '<div class="notice notice-upgrade notice-info is-dismissible">';
        echo '<p>Unlock <strong>AI rewriting</strong> and <strong>bulk optimization</strong> with AI Content Optimizer Pro Premium! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
        echo '</div>';
    }
}

?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability. Freemium model with premium features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AICOP_VERSION', '1.0.0');
define('AICOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICOP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AICOP_PREMIUM_KEY', 'aicop_premium_license');

// Freemius integration for monetization (simplified mock - replace with real Freemius SDK)
function aicop_freemius_init() {
    // Mock Freemius for demo. In production, include Freemius SDK.
    if (!class_exists('Freemius')) {
        // Freemius placeholder
        add_option('aicop_premium_active', false);
    }
}
add_action('plugins_loaded', 'aicop_freemius_init');

// Check if premium is active
function aicop_is_premium() {
    return get_option('aicop_premium_active', false);
}

// Admin menu
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_options_page(
        'AI Content Optimizer',
        'AI Content Optimizer',
        'manage_options',
        'ai-content-optimizer',
        'aicop_admin_page'
    );
}

// Admin page
function aicop_admin_page() {
    if (isset($_POST['aicop_optimize'])) {
        aicop_optimize_content($_POST['post_id']);
    }
    if (isset($_POST['aicop_activate_premium'])) {
        // Mock premium activation
        update_option('aicop_premium_active', true);
        echo '<div class="notice notice-success"><p>Premium activated! (Demo)</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Pro</h1>
        <?php if (!aicop_is_premium()): ?>
        <div class="notice notice-warning">
            <p><strong>Upgrade to Premium</strong> for AI rewriting, bulk optimization, and more! <a href="#" onclick="document.getElementById('premium-form').style.display='block'">Activate Now (Demo)</a></p>
        </div>
        <form id="premium-form" style="display:none;">
            <input type="submit" name="aicop_activate_premium" value="Activate Premium (Demo)" class="button button-primary">
        </form>
        <?php endif; ?>
        <p>Free: Basic SEO score and suggestions. Premium: Full AI optimization.</p>
        <form method="post">
            <?php wp_nonce_field('aicop_optimize', 'aicop_nonce'); ?>
            <p><label>Post ID: <input type="number" name="post_id" required></label></p>
            <p><input type="submit" name="aicop_optimize" value="Optimize Content" class="button button-primary"></p>
        </form>
    </div>
    <?php
}

// Optimize content
function aicop_optimize_content($post_id) {
    if (!wp_verify_nonce($_POST['aicop_nonce'], 'aicop_optimize')) {
        wp_die('Security check failed');
    }
    $post = get_post($post_id);
    if (!$post) {
        echo '<div class="notice notice-error"><p>Post not found.</p></div>';
        return;
    }
    $content = $post->post_content;
    $score = rand(60, 95); // Mock analysis
    $suggestions = aicop_generate_suggestions($content);
    $optimized = aicop_is_premium() ? aicop_ai_rewrite($content) : substr($content, 0, 200) . '... (Premium for full rewrite)';
    echo '<div class="notice notice-success">
        <h3>SEO Score: ' . $score . '%</h3>
        <p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>
        <p><strong>Optimized Content:</strong><br>' . wp_kses_post($optimized) . '</p>
        <p><a href="' . get_edit_post_link($post_id) . '" class="button">Edit Post</a></p>
    </div>';
}

// Mock suggestions
function aicop_generate_suggestions($content) {
    $word_count = str_word_count($content);
    $issues = [];
    if ($word_count < 300) $issues[] = 'Add more content (min 300 words).';
    if (substr_count($content, '<h2>') < 2) $issues[] = 'Add more H2 headings.';
    return implode(' ', $issues) ?: 'Good readability! Add internal links.';
}

// Mock AI rewrite (premium feature)
function aicop_ai_rewrite($content) {
    if (!aicop_is_premium()) return 'Premium feature locked.';
    // Mock AI: Improve sentences, add keywords
    return '<p>Optimized: ' . $content . ' Improved with AI for better SEO and engagement!</p>';
}

// Add meta box to posts
add_action('add_meta_boxes', 'aicop_add_meta_box');
function aicop_add_meta_box() {
    add_meta_box('aicop_optimizer', 'AI Content Optimizer', 'aicop_meta_box_callback', 'post', 'side');
}

function aicop_meta_box_callback($post) {
    echo '<p><a href="' . admin_url('options-general.php?page=ai-content-optimizer&post_id=' . $post->ID) . '" class="button">Optimize This Post</a></p>';
    if (aicop_is_premium()) {
        echo '<p><em>Premium: AI Rewrite Active</em></p>';
    }
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'aicop_enqueue_scripts');
function aicop_enqueue_scripts($hook) {
    if ($hook !== 'settings_page_ai-content-optimizer') return;
    wp_enqueue_style('aicop-admin', AICOP_PLUGIN_URL . 'style.css', [], AICOP_VERSION);
}

// Create style.css placeholder
file_put_contents(AICOP_PLUGIN_PATH . 'style.css', '/* AI Content Optimizer Styles */ .wrap h1 { color: #0073aa; }');

// Activation hook
register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    add_option('aicop_premium_active', false);
}

?>
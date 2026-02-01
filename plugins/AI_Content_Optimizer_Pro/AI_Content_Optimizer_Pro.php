/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO using AI-powered insights. Freemium model with premium upgrades.
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
define('AICOP_PREMIUM_URL', 'https://example.com/premium-upgrade');

// Freemium check - simulate license (in real, integrate with payment gateway)
function aicop_is_premium() {
    return get_option('aicop_premium_active', false);
}

function aicop_premium_nag() {
    if (!aicop_is_premium() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations and advanced features with <a href="' . AICOP_PREMIUM_URL . '" target="_blank">AI Content Optimizer Pro Premium</a> for just $9.99/month!</p></div>';
    }
}
add_action('admin_notices', 'aicop_premium_nag');

// Add meta box to post editor
add_action('add_meta_boxes', 'aicop_add_meta_box');
function aicop_add_meta_box() {
    add_meta_box('aicop-optimizer', 'AI Content Optimizer', 'aicop_meta_box_callback', 'post', 'side', 'high');
    add_meta_box('aicop-optimizer', 'AI Content Optimizer', 'aicop_meta_box_callback', 'page', 'side', 'high');
}

function aicop_meta_box_callback($post) {
    wp_nonce_field('aicop_optimize', 'aicop_nonce');
    $content = get_post_field('post_content', $post->ID);
    $score = get_post_meta($post->ID, '_aicop_score', true);
    $suggestions = get_post_meta($post->ID, '_aicop_suggestions', true);
    
    echo '<div id="aicop-results">';
    if ($score) {
        echo '<p><strong>SEO Score:</strong> ' . $score . '%</p>';
        if ($suggestions) {
            echo '<ul>';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul>';
        }
    }
    echo '</div>';
    echo '<p><button id="aicop-analyze" class="button button-primary">Analyze Content</button></p>';
    echo '<p class="description">' . (!aicop_is_premium() ? 'Free: Basic analysis (3/day). <a href="' . AICOP_PREMIUM_URL . '" target="_blank">Go Premium</a> for unlimited!' : 'Premium active!') . '</p>';
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'aicop_enqueue_scripts');
function aicop_enqueue_scripts($hook) {
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        wp_enqueue_script('aicop-admin', AICOP_PLUGIN_URL . 'assets/admin.js', ['jquery'], AICOP_VERSION, true);
        wp_enqueue_style('aicop-admin', AICOP_PLUGIN_URL . 'assets/admin.css', [], AICOP_VERSION);
        wp_localize_script('aicop-admin', 'aicop_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_ajax'),
            'is_premium' => aicop_is_premium() ? '1' : '0',
            'premium_url' => AICOP_PREMIUM_URL
        ]);
    }
}

// AJAX handler for analysis
add_action('wp_ajax_aicop_analyze', 'aicop_ajax_analyze');
function aicop_ajax_analyze() {
    check_ajax_referer('aicop_ajax', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }
    
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);
    
    // Simulate daily limit for free users
    $today = date('Y-m-d');
    $usage = get_transient('aicop_free_usage_' . get_current_user_id());
    if (!$usage) $usage = 0;
    if (!aicop_is_premium() && $usage >= 3) {
        wp_send_json_error('Daily limit reached. Upgrade to premium!');
    }
    
    // Simulate AI analysis (in real: integrate OpenAI API or similar)
    $word_count = str_word_count(strip_tags($content));
    $has_keywords = preg_match('/(seo|content|optimize)/i', $content);
    $score = min(100, 50 + ($word_count / 10) + ($has_keywords * 20));
    
    $suggestions = [];
    if ($word_count < 500) $suggestions[] = 'Add more content (aim for 1000+ words).';
    if (!$has_keywords) $suggestions[] = 'Include target keywords like "SEO" or "content optimization".';
    if ($score < 80) $suggestions[] = 'Improve readability and add headings.';
    
    if (!aicop_is_premium() && count($suggestions) > 2) {
        array_pop($suggestions); // Limit free suggestions
        $suggestions[] = 'Upgrade for full AI suggestions!';
    }
    
    update_post_meta($post_id, '_aicop_score', round($score));
    update_post_meta($post_id, '_aicop_suggestions', $suggestions);
    
    if (!aicop_is_premium()) {
        set_transient('aicop_free_usage_' . get_current_user_id(), $usage + 1, DAY_IN_SECONDS);
    }
    
    wp_send_json_success(['score' => round($score), 'suggestions' => $suggestions]);
}

// Admin menu for settings
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'aicop', 'aicop_settings_page');
}

function aicop_settings_page() {
    if (isset($_POST['aicop_premium_key'])) {
        // Simulate premium activation
        update_option('aicop_premium_active', true);
        echo '<div class="notice notice-success"><p>Premium activated! (Demo)</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Settings</h1>
        <form method="post">
            <?php wp_nonce_field('aicop_settings'); ?>
            <p><label>Enter Premium Key: <input type="text" name="aicop_premium_key" placeholder="premium-123"></label></p>
            <p class="description">Demo: Enter any value to activate premium features locally. Real version integrates with Stripe/PayPal.</p>
            <?php submit_button(); ?>
        </form>
        <p><a href="<?php echo AICOP_PREMIUM_URL; ?>" class="button button-primary" target="_blank">Upgrade to Premium</a></p>
    </div>
    <?php
}

// Create assets directories on activation
register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    $upload_dir = AICOP_PLUGIN_PATH . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    $js_dir = $upload_dir . '/js';
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
}

// Note: In production, add js/admin.js with AJAX logic, css/admin.css for styling.
// Real AI integration via OpenAI API key in settings.

?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability. Freemium: Basic free, premium features via subscription.
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

// Premium check (simulate license key for demo; integrate with Freemius or similar in production)
function aico_is_premium() {
    // In production, check license via API
    return false; // Free version
}

// Admin menu
add_action('admin_menu', 'aico_admin_menu');
function aico_admin_menu() {
    add_options_page(
        'AI Content Optimizer',
        'AI Content Opt.',
        'manage_options',
        'ai-content-optimizer',
        'aico_admin_page'
    );
}

// Admin page
function aico_admin_page() {
    if (isset($_POST['aico_optimize'])) {
        aico_optimize_content($_POST['post_id']);
    }
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer</h1>
        <p>Optimize your content for **SEO** and readability.</p>
        <?php if (!$post_id): ?>
            <p>Select a post to optimize: <a href="<?php echo admin_url('edit.php?post_type=post'); ?>" class="button">Edit Posts</a></p>
        <?php else: 
            $content = get_post_field('post_content', $post_id);
        ?>
        <form method="post">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <textarea name="content" rows="20" cols="100" style="width:100%;" placeholder="Paste content here"><?php echo esc_textarea($content); ?></textarea>
            <p><input type="submit" name="aico_optimize" class="button-primary" value="Optimize Content"></p>
        </form>
        <?php endif; ?>
        <div id="aico-premium-upsell">
            <h2>Go Premium!</h2>
            <ul>
                <li>AI-powered rewriting</li>
                <li>Bulk optimization</li>
                <li>Unlimited usage</li>
            </ul>
            <p><a href="https://example.com/premium" class="button button-large button-primary" target="_blank">Upgrade Now ($4.99/mo)</a></p>
        </div>
    </div>
    <?php
}

// Optimize content
function aico_optimize_content($post_id) {
    $content = sanitize_textarea_field($_POST['content']);
    
    // Basic free analysis: Word count, readability score (Flesch-Kincaid simulation)
    $word_count = str_word_count($content);
    $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
    $readability = 206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (/* avg syllables approx */ 1.5);
    
    // Basic suggestions
    $suggestions = [];
    if ($avg_sentence_length > 25) $suggestions[] = 'Shorten sentences for better readability.';
    if ($word_count < 300) $suggestions[] = 'Add more content for SEO.';
    
    // Premium feature gate
    if (aico_is_premium()) {
        // Simulate AI rewrite (in production, call OpenAI API)
        $optimized = aico_ai_rewrite($content);
    } else {
        $optimized = $content;
        echo '<div class="notice notice-info"><p>Basic optimization applied. <strong>Premium</strong> unlocks AI rewriting!</p></div>';
    }
    
    // Update post
    wp_update_post(['ID' => $post_id, 'post_content' => $optimized]);
    
    // Display results
    echo '<div class="notice notice-success">
        <p><strong>Results:</strong> Words: ' . $word_count . ', Readability: ' . round($readability, 2) . '</p>
        <ul>';
    foreach ($suggestions as $s) echo '<li>' . esc_html($s) . '</li>';
    echo '</ul></div>';
}

// Simulate AI rewrite (premium)
function aico_ai_rewrite($content) {
    // Placeholder: In production, use OpenAI or similar API
    return $content . '\n\n<!-- Optimized by AI -->';
}

// Add meta box to posts
add_action('add_meta_boxes', 'aico_add_meta_box');
function aico_add_meta_box() {
    add_meta_box('aico-optimizer', 'AI Content Optimizer', 'aico_meta_box_callback', 'post', 'side');
}

function aico_meta_box_callback($post) {
    echo '<p><a href="' . admin_url('options-general.php?page=ai-content-optimizer&post=' . $post->ID) . '" class="button">Optimize This Post</a></p>';
    if (!aico_is_premium()) {
        echo '<p><em>Upgrade for AI features!</em></p>';
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'aico_enqueue_scripts');
function aico_enqueue_scripts($hook) {
    if ('settings_page_ai-content-optimizer' !== $hook) return;
    wp_enqueue_style('aico-style', AICOP_PLUGIN_URL . 'style.css', [], AICOP_VERSION);
}

// Freemium upsell notice
add_action('admin_notices', 'aico_freemium_notice');
function aico_freemium_notice() {
    if (!current_user_can('manage_options')) return;
    if (aico_is_premium()) return;
    echo '<div class="notice notice-info">
        <p>Unlock <strong>AI rewriting</strong> and more with AI Content Optimizer Premium! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
    </div>';
}

// Create style.css placeholder
if (!file_exists(AICOP_PLUGIN_PATH . 'style.css')) {
    file_put_contents(AICOP_PLUGIN_PATH . 'style.css', '/* AI Content Optimizer Styles */ #aico-premium-upsell { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; }');
}

?>
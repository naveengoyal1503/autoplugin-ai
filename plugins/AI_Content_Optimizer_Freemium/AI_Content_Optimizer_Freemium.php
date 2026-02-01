/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis for SEO, readability, and engagement. Freemium model with premium upgrades.
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
define('AICOP_PREMIUM_URL', 'https://example.com/premium-upgrade');

// Freemium check - simulate license (in real, integrate with Freemius or similar)
function aicop_is_premium() {
    // For demo, check for a simple option; replace with real licensing
    return get_option('aicop_premium_active', false);
}

// Add admin menu
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_posts_page(
        'AI Content Optimizer',
        'AI Optimizer',
        'edit_posts',
        'ai-content-optimizer',
        'aicop_admin_page'
    );
}

// Admin page
function aicop_admin_page() {
    if (isset($_POST['aicop_optimize'])) {
        aicop_optimize_content($_POST['post_id']);
    }
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    $usage = get_option('aicop_usage_count', 0);
    $is_premium = aicop_is_premium();
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer</h1>
        <?php if (!$is_premium && $usage >= 5) : ?>
            <div class="notice notice-warning"><p>Free limit reached (5 analyses/month). <a href="<?php echo AICOP_PREMIUM_URL; ?>" target="_blank">Upgrade to Premium</a> for unlimited access!</p></div>
        <?php endif; ?>
        <p>Analyze selected post for SEO, readability, and engagement.</p>
        <form method="post">
            <?php if ($post_id) : ?>
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <p><input type="submit" name="aicop_optimize" class="button button-primary" value="Optimize This Post" <?php echo (!$is_premium && $usage >= 5) ? 'disabled' : ''; ?>></p>
            <?php else : ?>
                <p>Select a post from the Posts screen to optimize.</p>
            <?php endif; ?>
        </form>
        <h3>Usage: <?php echo $usage; ?>/5 (Free) | <a href="<?php echo AICOP_PREMIUM_URL; ?>" target="_blank">Go Premium</a></h3>
    </div>
    <?php
}

// Optimize content
function aicop_optimize_content($post_id) {
    $post = get_post($post_id);
    if (!$post) return;

    $content = $post->post_content;
    $title = $post->post_title;

    // Simulate AI analysis (replace with real AI API like OpenAI in premium)
    $word_count = str_word_count($content);
    $readability = 70 + (rand(0,30) - 15); // Simulated
    $seo_score = 65 + (rand(0,35) - 17); // Simulated
    $suggestions = [
        'Free: Add more keywords like "' . substr($title, 0, 10) . '".',
        'Improve readability: Shorten sentences.',
        'SEO: Add H2 tags.',
        aicop_is_premium() ? 'Premium: Advanced keyword density analysis.' : 'Premium: Unlock full suggestions.'
    ];

    // Check usage
    if (!aicop_is_premium()) {
        $usage = get_option('aicop_usage_count', 0);
        if ($usage >= 5) {
            echo '<div class="notice notice-error"><p>Limit reached. Upgrade now!</p></div>';
            return;
        }
        update_option('aicop_usage_count', $usage + 1);
    }

    // Display results
    echo '<div class="notice notice-success">';
    echo '<h3>Analysis Results:</h3>';
    echo '<ul>';
    echo '<li><strong>Word Count:</strong> ' . $word_count . '</li>';
    echo '<li><strong>Readability Score:</strong> ' . $readability . '%</li>';
    echo '<li><strong>SEO Score:</strong> ' . $seo_score . '%</li>';
    foreach ($suggestions as $sugg) {
        echo '<li>' . $sugg . '</li>';
    }
    echo '</ul>';
    if (!aicop_is_premium()) {
        echo '<p><a href="' . AICOP_PREMIUM_URL . '" target="_blank" class="button button-primary">Upgrade for Advanced Features</a></p>';
    }
    echo '</div>';
}

// Add meta box to posts
add_action('add_meta_boxes', 'aicop_add_meta_box');
function aicop_add_meta_box() {
    add_meta_box('aicop_optimizer', 'AI Content Optimizer', 'aicop_meta_box_content', 'post', 'side');
}

function aicop_meta_box_content($post) {
    echo '<p><a href="' . admin_url('edit.php?post_type=post&page=ai-content-optimizer&post=' . $post->ID) . '" class="button" target="_blank">Optimize Now</a></p>';
    echo '<p>Free: 5/month | <a href="' . AICOP_PREMIUM_URL . '" target="_blank">Premium</a></p>';
}

// Enqueue styles
add_action('admin_enqueue_scripts', 'aicop_enqueue_scripts');
function aicop_enqueue_scripts($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook && strpos($hook, 'ai-content-optimizer') === false) {
        return;
    }
    wp_enqueue_style('aicop-style', AICOP_PLUGIN_URL . 'style.css', [], AICOP_VERSION);
}

// Create style.css file placeholder (in real plugin, include file)
// Premium upsell notice
add_action('admin_notices', 'aicop_premium_notice');
function aicop_premium_notice() {
    if (!aicop_is_premium() && current_user_can('manage_options')) {
        $usage = get_option('aicop_usage_count', 0);
        if ($usage > 3) {
            echo '<div class="notice notice-info"><p>AI Content Optimizer: Unlock unlimited analyses and advanced AI features with <a href="' . AICOP_PREMIUM_URL . '" target="_blank">Premium ($9.99/mo)</a>! High conversion freemium model.</p></div>';
        }
    }
}

// Reset usage monthly (simplified)
add_action('wp', 'aicop_check_monthly_reset');
function aicop_check_monthly_reset() {
    $last_reset = get_option('aicop_last_reset', 0);
    $current_month = date('Y-m');
    if ($current_month !== date('Y-m', $last_reset)) {
        if (!aicop_is_premium()) {
            update_option('aicop_usage_count', 0);
        }
        update_option('aicop_last_reset', current_time('timestamp'));
    }
}

// Activation hook
register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    update_option('aicop_usage_count', 0);
    update_option('aicop_last_reset', current_time('timestamp'));
}

?>
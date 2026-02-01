/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI-powered insights. Freemium model with premium upgrades.
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
define('AICOP_PREMIUM_KEY', 'aicop_premium_key');

// Premium check function
function aicop_is_premium() {
    return get_option(AICOP_PREMIUM_KEY) === 'activated';
}

// Activation hook
register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    add_option('aicop_daily_scans', 0);
    add_option('aicop_last_reset', time());
}

// Daily scan reset
add_action('wp', 'aicop_check_reset_scans');
function aicop_check_reset_scans() {
    $last_reset = get_option('aicop_last_reset', 0);
    if (time() - $last_reset > 86400) { // 24 hours
        update_option('aicop_daily_scans', 0);
        update_option('aicop_last_reset', time());
    }
}

// Admin menu
add_action('admin_menu', 'aicop_admin_menu');
function aicop_admin_menu() {
    add_posts_page(
        'AI Content Optimizer',
        'AI Optimizer',
        'edit_posts',
        'aicop-optimizer',
        'aicop_admin_page'
    );
}

// Admin page
function aicop_admin_page() {
    $daily_scans = get_option('aicop_daily_scans', 0);
    $is_premium = aicop_is_premium();
    $scans_left = $is_premium ? 'Unlimited' : (5 - $daily_scans);
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Pro</h1>
        <p>Daily scans left: <strong><?php echo $scans_left; ?></strong></p>
        <?php if (!$is_premium && $daily_scans >= 5): ?>
            <div class="notice notice-warning"><p>Upgrade to premium for unlimited scans! <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>
        <?php endif; ?>
        <?php
        if (isset($_POST['aicop_optimize'])) {
            aicop_optimize_content($_POST['post_id']);
        }
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if ($post_id && (current_user_can('edit_post', $post_id)) && ($is_premium || $daily_scans < 5)) {
            echo '<form method="post">
                <input type="hidden" name="post_id" value="' . $post_id . '">
                <p><input type="submit" name="aicop_optimize" class="button button-primary" value="Optimize This Post with AI"></p>
            </form>';
        }
        if (!$is_premium): ?>
            <div class="card">
                <h2>Go Premium!</h2>
                <ul>
                    <li>Unlimited scans</li>
                    <li>Advanced AI suggestions</li>
                    <li>Priority support</li>
                    <li>API integrations</li>
                </ul>
                <a href="https://example.com/premium" class="button button-large button-primary" target="_blank">Upgrade Now - $9/mo</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Optimize function
function aicop_optimize_content($post_id) {
    if (!current_user_can('edit_post', $post_id)) return;

    $daily_scans = get_option('aicop_daily_scans', 0);
    if (!aicop_is_premium() && $daily_scans >= 5) {
        echo '<div class="notice notice-error"><p>Daily limit reached. <a href="https://example.com/premium" target="_blank">Upgrade</a>.</p></div>';
        return;
    }

    $post = get_post($post_id);
    $content = $post->post_content;
    $title = $post->post_title;

    // Simulate AI analysis (in real version, integrate OpenAI API or similar)
    $suggestions = aicop_generate_suggestions($title, $content);

    if (!aicop_is_premium()) {
        update_option('aicop_daily_scans', $daily_scans + 1);
    }

    echo '<div class="notice notice-success">
        <h3>AI Optimization Report</h3>
        <p><strong>SEO Score:</strong> ' . $suggestions['score'] . '%</p>
        <p><strong>Keyword Suggestions:</strong> ' . implode(', ', $suggestions['keywords']) . '</p>
        <p><strong>Improvements:</strong></p>
        <ul>';
    foreach ($suggestions['improvements'] as $imp) {
        echo '<li>' . $imp . '</li>';
    }
    echo '</ul>';
    if (aicop_is_premium()) {
        echo '<p><a href="#" onclick="applySuggestions(' . $post_id . ')">Apply Suggestions</a></p>';
    }
    echo '</div>';

    // Enqueue JS for premium apply
    if (aicop_is_premium()) {
        wp_enqueue_script('aicop-js', AICOP_PLUGIN_URL . 'assets.js', array('jquery'), AICOP_VERSION);
    }
}

// Mock AI suggestions
function aicop_generate_suggestions($title, $content) {
    $words = str_word_count(strip_tags($content));
    $score = min(95, 50 + ($words / 10));
    return array(
        'score' => $score,
        'keywords' => array('seo', 'content', 'wordpress'),
        'improvements' => array(
            'Add more headings (H2/H3)',
            'Include target keyword in first paragraph',
            'Aim for 1000+ words',
            'Add internal/external links',
            'Premium: Full AI rewrite available'
        )
    );
}

// Freemium upsell notice
add_action('admin_notices', 'aicop_freemium_notice');
function aicop_freemium_notice() {
    if (!aicop_is_premium() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info is-dismissible">
            <p>Unlock <strong>AI Content Optimizer Pro</strong> premium features! <a href="' . admin_url('edit.php?post_type=post&page=aicop-optimizer') . '">Upgrade Now</a></p>
        </div>';
    }
}

// Premium activation (demo - in real, verify license)
add_action('admin_post_aicop_activate_premium', 'aicop_activate_premium');
function aicop_activate_premium() {
    if (isset($_POST['premium_key']) && $_POST['premium_key'] === 'demo-premium-key') {
        update_option(AICOP_PREMIUM_KEY, 'activated');
        wp_redirect(admin_url('edit.php?post_type=post&page=aicop-optimizer'));
        exit;
    }
}

// Load assets (create empty assets.js for premium JS)
add_action('admin_enqueue_scripts', 'aicop_assets');
function aicop_assets($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    wp_enqueue_style('aicop-style', AICOP_PLUGIN_URL . 'style.css', array(), AICOP_VERSION);
}

?>
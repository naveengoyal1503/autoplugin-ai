/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('AICOP_VERSION', '1.0.0');
define('AICOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICOP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AICOP_PREMIUM_KEY', 'aicop_premium_license');

// Premium check function
function aicop_is_premium() {
    return get_option(AICOP_PREMIUM_KEY) === 'activated';
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
    if (isset($_POST['aicop_optimize'])) {
        aicop_optimize_content($_POST['post_id']);
    }
    if (isset($_POST['aicop_activate_premium'])) {
        update_option(AICOP_PREMIUM_KEY, 'activated');
        echo '<div class="notice notice-success"><p>Premium activated! (Demo - enter any key)</p></div>';
    }
    $screen = get_current_screen();
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Pro</h1>
        <?php if (!aicop_is_premium()): ?>
        <div class="notice notice-info">
            <p><strong>Go Premium!</strong> Unlock unlimited optimizations, AI rewrites, and more for $4.99/mo. <form method="post"><input type="submit" name="aicop_activate_premium" value="Activate Demo" class="button-primary"></form></p>
        </div>
        <?php endif; ?>
        <p>Free scans limited to 3 per day. Premium: Unlimited.</p>
        <form method="post">
            <select name="post_id">
                <?php
                $posts = get_posts(['numberposts' => 20]);
                foreach ($posts as $post) {
                    echo '<option value="' . $post->ID . '">' . esc_html($post->post_title) . '</option>';
                }
                ?>
            </select>
            <input type="submit" name="aicop_optimize" value="Optimize Content" class="button-primary">
        </form>
        <div id="aicop-results"></div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('form').on('submit', function(e) {
            e.preventDefault();
            var postId = $('select[name="post_id"]').val();
            $.post(ajaxurl, {action: 'aicop_ajax_optimize', post_id: postId, nonce: '<?php echo wp_create_nonce('aicop_nonce'); ?>'}, function(response) {
                $('#aicop-results').html(response);
            });
        });
    });
    </script>
    <?php
}

// AJAX handler
add_action('wp_ajax_aicop_ajax_optimize', 'aicop_ajax_optimize');
function aicop_ajax_optimize() {
    check_ajax_referer('aicop_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_die('Invalid post');

    $daily_count = get_transient('aicop_free_scans_' . get_current_user_id());
    if (!$aicop_is_premium() && $daily_count >= 3) {
        wp_die('Free limit reached. <a href="#" onclick="jQuery('.\''form input[name="aicop_activate_premium"]\''.').click();">Upgrade to Premium</a>');
    }

    if (!$aicop_is_premium()) {
        set_transient('aicop_free_scans_' . get_current_user_id(), $daily_count + 1, DAY_IN_SECONDS);
    }

    echo aicop_generate_report($post_id);
    wp_die();
}

// Generate optimization report
function aicop_generate_report($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    $word_count = str_word_count(strip_tags($content));
    $title_length = strlen($post->post_title);
    $has_h1 = preg_match('/<h1[^>]*>/i', $post->post_content);
    $keyword_density = 2.5; // Simulated

    $report = '<div class="aicop-report">';
    $report .= '<h3>Optimization Report for: ' . esc_html($post->post_title) . '</h3>';
    $report .= '<ul>';
    $report .= '<li><strong>Word Count:</strong> ' . $word_count . ' (Optimal: 1000-2000)</li>';
    $report .= '<li><strong>Title Length:</strong> ' . $title_length . ' chars (Optimal: 50-60)</li>';
    $report .= '<li><strong>H1 Present:</strong> ' . ($has_h1 ? 'Yes' : 'No') . '</li>';
    $report .= '<li><strong>Keyword Density:</strong> ' . $keyword_density . '% (Optimal: 1-2%)</li>';
    if (aicop_is_premium()) {
        $report .= '<li><strong>AI Rewrite:</strong> <button id="ai-rewrite">Generate Rewrite</button></li>';
    } else {
        $report .= '<li>Premium: AI rewrites & more!</li>';
    }
    $report .= '</ul>';
    $report .= '</div>';
    return $report;
}

// Simulate premium AI rewrite (basic placeholder)
add_action('wp_ajax_aicop_rewrite', 'aicop_rewrite_content');
function aicop_rewrite_content() {
    if (!aicop_is_premium()) wp_die('Premium only');
    // Integrate with OpenAI API here in full version
    echo 'Optimized version: Your content rewritten for max engagement! (Premium feature demo)';
    wp_die();
}

// Add meta box to posts
add_action('add_meta_boxes', 'aicop_add_meta_box');
function aicop_add_meta_box() {
    add_meta_box('aicop-optimizer', 'Quick AI Optimize', 'aicop_meta_box_callback', 'post', 'side');
}
function aicop_meta_box_callback($post) {
    echo '<p><a href="' . admin_url('admin.php?page=ai-content-optimizer') . '" class="button">Optimize This Post</a></p>';
    if (!aicop_is_premium()) {
        echo '<p><em>Upgrade for AI magic!</em></p>';
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'aicop_enqueue_scripts');
function aicop_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ai-content-optimizer') return;
    wp_enqueue_script('jquery');
}

register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    // Set defaults
}

register_deactivation_hook(__FILE__, 'aicop_deactivate');
function aicop_deactivate() {
    // Cleanup
}
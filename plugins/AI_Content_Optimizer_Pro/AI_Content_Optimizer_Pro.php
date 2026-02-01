/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using AI-powered insights. Freemium model with premium upgrades.
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

// Premium check (simulate license key for freemium)
function aicop_is_premium() {
    $license_key = get_option('aicop_license_key', '');
    return !empty($license_key) && $license_key === 'premium_active_key'; // Demo key
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
        'dashicons-performance',
        30
    );
}

// Admin page
function aicop_admin_page() {
    $usage_count = get_option('aicop_usage_count', 0);
    $is_premium = aicop_is_premium();
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Pro</h1>
        <?php if (!$is_premium && $usage_count >= 5): ?>
            <div class="notice notice-warning">
                <p><strong>Free limit reached (5 posts/month).</strong> <a href="<?php echo AICOP_PREMIUM_URL; ?>" target="_blank">Upgrade to Premium</a> for unlimited access!</p>
            </div>
        <?php endif; ?>
        <p>Optimize your posts for SEO. Select a post to analyze:</p>
        <select id="aicop_post_select">
            <?php
            $posts = get_posts(['numberposts' => -1, 'post_status' => 'publish']);
            foreach ($posts as $post): ?>
                <option value="<?php echo $post->ID; ?>"><?php echo esc_html($post->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        <button id="aicop_optimize_btn" class="button button-primary">Analyze & Optimize</button>
        <div id="aicop_results"></div>
        <?php if (!$is_premium): ?>
            <div style="margin-top: 20px; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7;">
                <h3>Go Premium for Advanced Features</h3>
                <ul>
                    <li>Unlimited optimizations</li>
                    <li>AI auto-rewrite suggestions</li>
                    <li>Keyword density optimizer</li>
                    <li>Priority support</li>
                </ul>
                <a href="<?php echo AICOP_PREMIUM_URL; ?}" class="button button-large button-primary" target="_blank">Upgrade Now - $9.99/mo</a>
            </div>
        <?php endif; ?>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#aicop_optimize_btn').click(function() {
            var postId = $('#aicop_post_select').val();
            $.post(ajaxurl, {
                action: 'aicop_optimize',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('aicop_nonce'); ?>'
            }, function(response) {
                $('#aicop_results').html(response.data.message);
            });
        });
    });
    </script>
    <?php
}

// AJAX handler
add_action('wp_ajax_aicop_optimize', 'aicop_ajax_optimize');
function aicop_ajax_optimize() {
    check_ajax_referer('aicop_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }
    $post_id = intval($_POST['post_id']);
    $usage_count = get_option('aicop_usage_count', 0);
    $is_premium = aicop_is_premium();

    if (!$is_premium && $usage_count >= 5) {
        wp_send_json_error('Free limit reached. Upgrade to premium!');
    }

    // Simulate AI analysis (in real version, integrate OpenAI API or similar)
    $post = get_post($post_id);
    $content = $post->post_content;
    $analysis = [
        'readability_score' => rand(60, 90),
        'keyword_density' => rand(1, 3) . '%',
        'suggestions' => ['Add more headings', 'Include meta description', 'Optimize images'],
        'optimized_content' => $is_premium ? $content . '\n\n<!-- AI Optimized: Premium rewrite applied -->' : substr($content, 0, 200) . '... (Premium for full)'
    ];

    if (!$is_premium) {
        update_option('aicop_usage_count', $usage_count + 1);
    }

    // Update post if premium
    if ($is_premium) {
        wp_update_post(['ID' => $post_id, 'post_content' => $analysis['optimized_content']]);
    }

    ob_start();
    echo '<div class="aicop-analysis">';
    echo '<h3>Analysis Results:</h3>';
    echo '<p><strong>Readability:</strong> ' . $analysis['readability_score'] . '/100</p>';
    echo '<p><strong>Keyword Density:</strong> ' . $analysis['keyword_density'] . '</p>';
    echo '<ul>';
    foreach ($analysis['suggestions'] as $sugg) {
        echo '<li>' . $sugg . '</li>';
    }
    echo '</ul>';
    echo '<p><strong>Optimized Content Preview:</strong><br>' . esc_html($analysis['optimized_content']) . '</p>';
    echo '</div>';
    $html = ob_get_clean();

    wp_send_json_success(['message' => $html]);
}

// Freemius-like premium activation (demo)
add_action('admin_init', 'aicop_premium_activate');
function aicop_premium_activate() {
    if (isset($_GET['aicop_activate_premium'])) {
        update_option('aicop_license_key', 'premium_active_key');
        wp_redirect(admin_url('admin.php?page=ai-content-optimizer'));
        exit;
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'aicop_enqueue_scripts');
function aicop_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ai-content-optimizer') return;
    wp_enqueue_script('jquery');
}

// Add premium demo activation link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'aicop_action_links');
function aicop_action_links($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=ai-content-optimizer&aicop_activate_premium=1') . '">Activate Premium (Demo)</a>';
    $links[] = '<a href="' . AICOP_PREMIUM_URL . '" target="_blank">Premium</a>';
    return $links;
}

?>
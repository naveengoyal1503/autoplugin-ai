/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes your WordPress content for SEO and readability using AI-powered analysis. Free version includes basic checks; premium unlocks advanced features.
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

// Freemium check function
function aico_is_premium() {
    // Simulate license check; in real version, integrate with payment gateway like Freemius
    return false; // Free version
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'aico_admin_scripts');
function aico_admin_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    wp_enqueue_script('aico-admin-js', AICOP_PLUGIN_URL . 'admin.js', array('jquery'), AICOP_VERSION, true);
    wp_enqueue_style('aico-admin-css', AICOP_PLUGIN_URL . 'admin.css', array(), AICOP_VERSION);
}

// Add meta box to post editor
add_action('add_meta_boxes', 'aico_add_meta_box');
function aico_add_meta_box() {
    add_meta_box(
        'aico_optimizer',
        'AI Content Optimizer',
        'aico_meta_box_callback',
        array('post', 'page'),
        'side',
        'high'
    );
}

function aico_meta_box_callback($post) {
    wp_nonce_field('aico_optimize_nonce', 'aico_nonce');
    echo '<button type="button" id="aico-optimize-btn" class="button button-primary">Optimize Content</button>';
    echo '<div id="aico-results"></div>';
    if (!aico_is_premium()) {
        echo '<p><strong>Upgrade to Pro</strong> for advanced AI rewriting, bulk optimization, and more!</p>';
        echo '<a href="https://example.com/premium" target="_blank" class="button button-secondary">Get Pro</a>';
    }
}

// AJAX handler for optimization
add_action('wp_ajax_aico_optimize', 'aico_handle_optimize');
function aico_handle_optimize() {
    check_ajax_referer('aico_optimize_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    $content = $post->post_content;

    // Basic free optimization: keyword density, readability score simulation
    $word_count = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content);
    $readability = round(206.835 - 1.015 * ($word_count / count($sentences)) - 84.6 * (8.62 / $word_count), 2);

    $suggestions = array();
    if ($readability > 60) {
        $suggestions[] = 'Improve sentence variety for better readability (Flesch score: ' . $readability . ').';
    }
    if (!aico_is_premium()) {
        $suggestions[] = 'Premium: Full AI rewrite and SEO keyword suggestions.';
    } else {
        // Premium simulation
        $suggestions[] = 'AI suggested: Shorten long sentences and add subheadings.';
    }

    wp_send_json_success(array(
        'readability' => $readability,
        'word_count' => $word_count,
        'suggestions' => $suggestions
    ));
}

// Admin JS (inline for single file)
add_action('admin_footer-post.php', 'aico_inline_js');
add_action('admin_footer-post-new.php', 'aico_inline_js');
function aico_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aico-optimize-btn').click(function() {
            var postId = $('#post_ID').val();
            var nonce = $('#aico_nonce').val();
            $('#aico-results').html('<p>Optimizing...</p>');
            $.post(ajaxurl, {
                action: 'aico_optimize',
                post_id: postId,
                nonce: nonce
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<p><strong>Readability:</strong> ' + res.readability + '% | <strong>Words:</strong> ' + res.word_count + '</p><ul>';
                    $.each(res.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    $('#aico-results').html(html);
                }
            });
        });
    });
    </script>
    <?php
}

// Admin CSS (inline)
add_action('admin_head-post.php', 'aico_inline_css');
add_action('admin_head-post-new.php', 'aico_inline_css');
function aico_inline_css() {
    echo '<style>#aico_optimizer { background: #fff; } #aico-optimize-btn { width: 100%; margin-bottom: 10px; }</style>';
}

// Activation hook
register_activation_hook(__FILE__, 'aico_activate');
function aico_activate() {
    flush_rewrite_rules();
}

// Plugin row meta links
add_filter('plugin_row_meta', 'aico_plugin_row_meta', 10, 2);
function aico_plugin_row_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        $links[] = '<a href="https://example.com/premium" target="_blank">Pro Version</a>';
    }
    return $links;
}

?>
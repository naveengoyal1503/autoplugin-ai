/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered readability, SEO, and engagement analysis. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

const $AICOP_VERSION = '1.0.0';
const $AICOP_PLUGIN_FILE = __FILE__;

define('AICOP_FREE_VERSION', true);

// Freemius integration (requires Freemius SDK - in production, include via composer or download)
// For this single-file demo, simulate Freemius with basic licensing check
function aicop_freemius_init() {
    // In production, require_once dirname(__FILE__) . '/freemius/start.php';
    // return fs_dynamic_init();
    return null;
}
$fs = aicop_freemius_init();

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'aicop_admin_scripts');
function aicop_admin_scripts($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }
    wp_enqueue_script('aicop-admin-js', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], $AICOP_VERSION, true);
    wp_enqueue_style('aicop-admin-css', plugin_dir_url(__FILE__) . 'admin.css', [], $AICOP_VERSION);
    wp_localize_script('aicop-admin-js', 'aicop_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aicop_nonce'),
        'is_premium' => aicop_is_premium(),
        'scans_left' => aicop_get_scans_left(),
    ]);
}

// Add meta box to post editor
add_action('add_meta_boxes', 'aicop_add_meta_box');
function aicop_add_meta_box() {
    add_meta_box('aicop-optimizer', 'AI Content Optimizer', 'aicop_meta_box_callback', ['post', 'page'], 'side', 'high');
}

function aicop_meta_box_callback($post) {
    wp_nonce_field('aicop_meta_box', 'aicop_meta_box_nonce');
    echo '<div id="aicop-results"></div>';
    echo '<button id="aicop-scan" class="button button-primary">' . (aicop_is_premium() ? 'Optimize Now' : 'Scan Content (Free)') . '</button>';
    if (!aicop_is_premium()) {
        $scans_left = aicop_get_scans_left();
        echo '<p><small>' . sprintf(_n('%d free scan left this month.', '%d free scans left this month.', $scans_left, 'ai-content-optimizer'), $scans_left) . '</small></p>';
        echo '<p><a href="#" id="aicop-upgrade">Upgrade to Premium for Unlimited!</a></p>';
    }
}

// AJAX handler for scan
add_action('wp_ajax_aicop_scan', 'aicop_handle_scan');
function aicop_handle_scan() {
    check_ajax_referer('aicop_nonce', 'nonce');

    if (!aicop_can_scan()) {
        wp_die(json_encode(['error' => 'Scan limit reached or invalid license.']));
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

    // Simulate AI analysis (in production, integrate OpenAI API or similar)
    $score = rand(60, 95);
    $readability = rand(70, 100);
    $seo = rand(50, 90);
    $suggestions = aicop_generate_suggestions($content, $score);

    aicop_decrement_scans();

    wp_die(json_encode([
        'score' => $score,
        'readability' => $readability,
        'seo' => $seo,
        'suggestions' => $suggestions,
        'is_premium' => aicop_is_premium(),
    ]));
}

// Simulate premium check (replace with Freemius is_premium())
function aicop_is_premium() {
    return get_option('aicop_premium_active', false); // Demo: set to true for testing
}

// Free scan limits
function aicop_get_scans_left() {
    if (aicop_is_premium()) return 999;
    $used = get_option('aicop_scans_used_' . date('Y-m'), 0);
    return max(0, 5 - $used);
}

function aicop_can_scan() {
    return aicop_get_scans_left() > 0;
}

function aicop_decrement_scans() {
    if (!aicop_is_premium()) {
        $month = date('Y-m');
        $used = intval(get_option('aicop_scans_used_' . $month, 0)) + 1;
        update_option('aicop_scans_used_' . $month, $used);
    }
}

function aicop_generate_suggestions($content, $score) {
    $sugs = [];
    if ($score < 80) $sugs[] = 'Add more subheadings and short paragraphs for better readability.';
    $sugs[] = 'Include 2-3 target keywords naturally.';
    if (aicop_is_premium()) {
        $sugs[] = 'Premium: AI-generated rewrite available.';
    }
    return $sugs;
}

// Premium upsell notice
add_action('admin_notices', 'aicop_premium_notice');
function aicop_premium_notice() {
    if (aicop_is_premium() || aicop_get_scans_left() > 0) return;
    echo '<div class="notice notice-info"><p>';
    echo sprintf(
        'AI Content Optimizer: <strong>0 free scans left</strong>. <a href="%s" target="_blank">Upgrade to Premium</a> for unlimited access!',
        'https://example.com/premium'
    );
    echo '</p></div>';
}

// Activation hook
register_activation_hook(__FILE__, 'aicop_activate');
function aicop_activate() {
    // Freemius opt-in etc.
}

// Plugin row meta
add_filter('plugin_row_meta', 'aicop_plugin_row_meta', 10, 2);
function aicop_plugin_row_meta($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a> | ';
        $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
    }
    return $links;
}

// Note: For full production, add admin.js, admin.css, Freemius SDK, real AI API integration (e.g., OpenAI),
// settings page, and proper i18n. This is a self-contained functional demo.
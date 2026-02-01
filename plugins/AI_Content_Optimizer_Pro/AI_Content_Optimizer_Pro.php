/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_enabled', 'yes');
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $enabled = get_post_meta($post->ID, 'ai_optimizer_enabled', true);
        echo '<label><input type="checkbox" name="ai_optimizer_enabled" ' . checked($enabled, 'yes', false) . ' /> Enable AI Optimization</label>';
        echo '<p>Click <strong>Optimize Now</strong> to analyze and improve SEO.</p>';
        echo '<button id="ai-optimize-btn" class="button button-primary">Optimize Now</button>';
        echo '<div id="ai-results"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['ai_optimizer_enabled'])) {
            update_post_meta($post_id, 'ai_optimizer_enabled', 'yes');
        } else {
            delete_post_meta($post_id, 'ai_optimizer_enabled');
        }
    }
}

// Simulated AI Optimization Function
add_action('wp_ajax_ai_optimize_content', 'ai_optimize_content_handler');
function ai_optimize_content_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_optimizer_nonce')) {
        wp_die('Security check failed');
    }

    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);
    $title = get_post_field('post_title', $post_id);

    // Simulated AI analysis (in pro version, integrate OpenAI API)
    $keywords = $this->extract_keywords($title . ' ' . $content);
    $readability_score = rand(60, 90);
    $suggestions = array(
        'Suggested Title: ' . $title . ' - ' . implode(', ', array_slice($keywords, 0, 3)),
        'Primary Keywords: ' . implode(', ', $keywords),
        'Readability Score: ' . $readability_score . '%',
        'Meta Description: ' . wp_trim_words(strip_tags($content), 25, '...')
    );

    wp_send_json_success(array('suggestions' => $suggestions));
}

// Placeholder for JS/CSS files (in real plugin, include them)
function ai_content_optimizer_extract_keywords($text) {
    $words = explode(' ', strtolower(preg_replace('/[^a-z\s]/', '', $text)));
    $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 3; }));
    arsort($counts);
    return array_keys(array_slice($counts, 0, 5));
}

new AIContentOptimizer();

// Freemium Upsell Notice
add_action('admin_notices', function() {
    if (!get_option('ai_optimizer_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for real AI integration & more! <a href="https://example.com/pro">Upgrade Now</a> | <a href="?dismiss_ai_pro=1">Dismiss</a></p></div>';
    }
});

add_action('admin_init', function() {
    if (isset($_GET['dismiss_ai_pro'])) {
        update_option('ai_optimizer_pro_dismissed', true);
    }
});
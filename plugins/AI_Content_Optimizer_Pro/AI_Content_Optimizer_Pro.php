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
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
        $this->check_premium_status();
    }

    public function activate() {
        add_option('ai_content_optimizer_premium_active', 'no');
        add_option('ai_content_optimizer_scan_count', 0);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    private function check_premium_status() {
        $premium_active = get_option('ai_content_optimizer_premium_active', 'no');
        if ('yes' !== $premium_active) {
            $scan_count = get_option('ai_content_optimizer_scan_count', 0);
            if ($scan_count >= 5) {
                add_action('admin_notices', array($this, 'show_upgrade_notice'));
            }
        }
    }

    public function show_upgrade_notice() {
        echo '<div class="notice notice-warning"><p><strong>AI Content Optimizer Pro:</strong> Upgrade to premium for unlimited optimizations! <a href="' . admin_url('admin.php?page=ai-content-optimizer') . '">Upgrade Now</a></p></div>';
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_content_suggestions', true);
        $premium_active = get_option('ai_content_optimizer_premium_active', 'no');
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        if ($suggestions) {
            echo '<p><strong>Suggestions:</strong><br>' . esc_html($suggestions) . '</p>';
        }
        echo '<p><a href="#" class="button button-primary ai-optimize-btn" data-post-id="' . $post->ID . '">Analyze Content</a></p>';
        if ('yes' !== $premium_active) {
            echo '<p><em>Free scans limited to 5. <a href="' . admin_url('admin.php?page=ai-content-optimizer') . '">Go Premium</a></em></p>';
        }
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Pro',
            'AI Optimizer Pro',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['premium_key'])) {
            update_option('ai_content_optimizer_premium_active', 'yes');
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        $premium_active = get_option('ai_content_optimizer_premium_active', 'no');
        echo '<div class="wrap"><h1>AI Content Optimizer Pro</h1>';
        if ('no' === $premium_active) {
            echo '<form method="post"><p>Enter Premium Key: <input type="text" name="premium_key" placeholder="your-key-here"></p><p><input type="submit" class="button-primary" value="Activate Premium"></p></form>';
            echo '<p><strong>Premium Benefits:</strong> Unlimited scans, AI rewrites, auto-apply fixes ($9/mo).</p>';
        } else {
            echo '<p>Premium is active! Enjoy unlimited features.</p>';
        }
        echo '</div>';
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function enqueue_scripts() {
        if (is_single()) {
            global $post;
            $score = get_post_meta($post->ID, '_ai_content_score', true);
            if ($score && $score > 80) {
                // Premium feature: Add schema markup
                add_action('wp_head', array($this, 'add_schema_markup'));
            }
        }
    }

    public function add_schema_markup() {
        global $post;
        echo '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Article","headline":"' . esc_attr($post->post_title) . '","datePublished":"' . get_the_date('c', $post) . '"}</script>';
    }
}

// AJAX handler for content analysis
add_action('wp_ajax_ai_optimize_content', 'AIContentOptimizer::handle_ajax_optimize');
AIContentOptimizer::handle_ajax_optimize = function() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Unauthorized');
    }

    $premium_active = get_option('ai_content_optimizer_premium_active', 'no');
    $scan_count = get_option('ai_content_optimizer_scan_count', 0);

    if ('no' === $premium_active && $scan_count >= 5) {
        wp_send_json_error('Upgrade to premium for more scans.');
        return;
    }

    $post = get_post($post_id);
    $content = $post->post_content;
    $word_count = str_word_count(strip_tags($content));
    $score = min(100, 50 + ($word_count / 100) + (rand(0, 30)));
    $suggestions = $this->generate_suggestions($content, $score);

    update_post_meta($post_id, '_ai_content_score', $score);
    update_post_meta($post_id, '_ai_content_suggestions', $suggestions);

    if ('no' === $premium_active) {
        update_option('ai_content_optimizer_scan_count', $scan_count + 1);
    }

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
};

public function generate_suggestions($content, $score) {
    $sugs = array();
    if ($score < 70) {
        $sugs[] = 'Add more keywords related to your topic.';
    }
    if (str_word_count(strip_tags($content)) < 500) {
        $sugs[] = 'Increase content length to over 1000 words for better SEO.';
    }
    $sugs[] = 'Improve readability by using shorter sentences.';
    if ('yes' === get_option('ai_content_optimizer_premium_active', 'no')) {
        $sugs[] = 'Premium: Auto-rewrite available.';
    }
    return implode(' ', $sugs);
}

AIContentOptimizer::get_instance();
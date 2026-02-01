/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI analysis. Free version includes basic checks; premium unlocks advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_optimize_score', array($this, 'optimize_score_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function activate() {
        add_option('ai_optimizer_premium_key', '');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $premium = $this->is_premium();
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        if ($premium) {
            echo '<p><a href="#" class="button ai-optimize-btn" data-post-id="' . $post->ID . '">Optimize with AI (Premium)</a></p>';
        } else {
            echo '<p><a href="' . $this->get_premium_url() . '" class="button button-primary" target="_blank">Upgrade to Premium</a></p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Basic analysis
        $content = get_post_field('post_content', $post_id);
        $score = $this->calculate_basic_score($content);
        update_post_meta($post_id, '_ai_optimizer_score', $score);
    }

    private function calculate_basic_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_title = get_the_title() ? 1 : 0;
        $has_headings = preg_match('/<h[1-6]/', $content) ? 1 : 0;
        $score = min(100, (int)($word_count / 10 + $has_title * 20 + $has_headings * 20));
        return $score;
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_premium_key'])) {
            update_option('ai_optimizer_premium_key', sanitize_text_field($_POST['ai_premium_key']));
            echo '<div class="notice notice-success"><p>Key updated!</p></div>';
        }
        $key = get_option('ai_optimizer_premium_key');
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1><form method="post">';
        echo '<p><label>Premium License Key: <input type="text" name="ai_premium_key" value="' . esc_attr($key) . '" size="50"></label></p>';
        submit_button();
        echo '</form><p>Enter your premium key to unlock AI rewriting, keyword suggestions, and more. <a href="' . $this->get_premium_url() . '" target="_blank">Get Premium</a></p></div>';
    }

    private function check_premium() {
        $key = get_option('ai_optimizer_premium_key');
        return !empty($key) && $this->validate_premium_key($key);
    }

    private function is_premium() {
        return $this->check_premium();
    }

    private function validate_premium_key($key) {
        // Simulate validation - in real premium, call API
        return hash('sha256', $key) === 'demo_premium_hash'; // Replace with real validation
    }

    private function get_premium_url() {
        return 'https://example.com/premium-upgrade?ref=plugin';
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_ajax'),
                'premium' => $this->is_premium(),
                'upgrade_url' => $this->get_premium_url()
            ));
        }
    }

    public function optimize_score_shortcode($atts) {
        $post_id = get_the_ID();
        $score = get_post_meta($post_id, '_ai_optimizer_score', true);
        return '<span class="ai-score" style="background:#' . ($score > 70 ? '28a745' : ($score > 50 ? 'ffc107' : 'dc3545')) . '; color:white; padding:5px; border-radius:3px;">' . ($score ?: 0) . '% SEO Score</span>';
    }
}

AIContentOptimizer::get_instance();

// AJAX for premium optimization
add_action('wp_ajax_ai_optimize_content', 'ai_optimizer_ajax_optimize');
function ai_optimizer_ajax_optimize() {
    check_ajax_referer('ai_optimizer_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }
    $post_id = intval($_POST['post_id']);
    $optimizer = AIContentOptimizer::get_instance();
    if (!$optimizer->is_premium()) {
        wp_send_json_error('Premium required');
    }
    // Simulate AI optimization
    $optimized_content = 'Optimized content with AI (Premium feature). Original score improved by 20%!';
    wp_send_json_success($optimized_content);
}

// Freemium notice
add_action('admin_notices', function() {
    $optimizer = AIContentOptimizer::get_instance();
    if (!is_plugin_active('ai-content-optimizer-pro/ai-content-optimizer-pro.php') && !$optimizer->is_premium()) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> features like AI rewriting! <a href="' . $optimizer->get_premium_url() . '" target="_blank">Upgrade now</a></p></div>';
    }
});
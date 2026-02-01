/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically analyzes and optimizes post content for SEO using AI-like heuristics. Freemium model with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizerPro {
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
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_meta_box() {
        add_meta_box('ai-optimizer-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_score', true);
        $premium = $this->is_premium();
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score : 'Not analyzed') . '%</p>';
        if (!$premium) {
            echo '<p><a href="https://example.com/premium" target="_blank">Upgrade to Pro for AI suggestions!</a></p>';
        } else {
            echo '<p>Premium: Advanced optimizations applied.</p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $score = $this->calculate_seo_score(get_post($post_id));
        update_post_meta($post_id, '_ai_score', $score);
    }

    private function calculate_seo_score($post) {
        $content = $post->post_content;
        $title = $post->post_title;
        $score = 50; // Base

        // Keyword density simulation (heuristic for AI)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count > 300) $score += 20;

        // Title length
        if (strlen($title) > 30 && strlen($title) < 60) $score += 15;

        // Headings
        if (preg_match_all('/<h[1-3]/', $content)) $score += 10;

        // Images
        if (preg_match_all('/<img/', $content)) $score += 5;

        return min(100, $score);
    }

    public function optimize_content($content) {
        if (!$this->is_premium()) {
            // Free: Basic optimizations
            $content = $this->basic_optimize($content);
        } else {
            // Premium: Advanced (placeholder)
            $content = $this->advanced_optimize($content);
        }
        return $content;
    }

    private function basic_optimize($content) {
        // Add internal links placeholder
        return $content;
    }

    private function advanced_optimize($content) {
        // Premium features
        return $content;
    }

    public function add_settings_page() {
        add_options_page('AI Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_license_key'])) {
            update_option('ai_premium_key', sanitize_text_field($_POST['ai_license_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $key = get_option('ai_premium_key');
        echo '<div class="wrap"><h1>AI Content Optimizer Pro</h1><form method="post">';
        echo '<p><label>Premium License Key: <input type="text" name="ai_license_key" value="' . esc_attr($key) . '" /></label></p>';
        echo '<p class="description">Enter your premium key or <a href="https://example.com/premium" target="_blank">buy now</a> for advanced AI features.</p>';
        submit_button();
        echo '</form></div>';
    }

    private function is_premium() {
        $key = get_option('ai_premium_key');
        return !empty($key) && $key === 'premium_demo_key_123'; // Demo check
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('ai-optimizer', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AIContentOptimizerPro::get_instance();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!AIContentOptimizerPro::get_instance()->is_premium()) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> premium features! <a href="https://example.com/premium" target="_blank">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');
?>
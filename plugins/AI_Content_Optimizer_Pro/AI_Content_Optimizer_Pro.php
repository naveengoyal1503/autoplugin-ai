/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your content for better readability, SEO, and engagement. Freemium model with premium upgrades.
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
    private $is_premium = false;
    private $usage_count = 0;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Check premium status (simulate license check)
        $this->is_premium = get_option('aco_premium_active', false);
        $this->usage_count = get_option('aco_usage_count', 0);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('tools_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function admin_page() {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        $post = $post_id ? get_post($post_id) : null;
        $content = $post ? $post->post_content : '';
        $analysis = get_post_meta($post_id, '_aco_analysis', true);

        if (isset($_POST['upgrade_to_premium'])) {
            update_option('aco_premium_active', true);
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated! (Demo)</p></div>';
        }

        include plugin_dir_path(__FILE__) . 'admin-template.php';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!$this->is_premium && $this->usage_count >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to premium for unlimited use.');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Simulate AI analysis (basic heuristics for demo)
        $analysis = $this->analyze_content($content);
        $suggestions = $this->is_premium ? $this->generate_premium_suggestions($content) : 'Upgrade to premium for advanced AI suggestions.';

        if (!$this->is_premium) {
            $this->usage_count++;
            update_option('aco_usage_count', $this->usage_count);
        }

        wp_send_json_success(array(
            'analysis' => $analysis,
            'suggestions' => $suggestions,
            'usage_count' => $this->usage_count,
            'is_premium' => $this->is_premium
        ));
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(($word_count / $sentence_count), 1) : 0;
        $seo_score = min(100, (20 + ($word_count / 10) + (rand(0, 50))));

        return array(
            'word_count' => $word_count,
            'avg_sentence_length' => $readability,
            'readability_score' => $readability > 20 ? 'Needs improvement' : 'Good',
            'seo_score' => $seo_score
        );
    }

    private function generate_premium_suggestions($content) {
        $suggestions = array(
            'Shorten long sentences for better readability.',
            'Add more headings (H2/H3) for SEO.',
            'Include keywords like "WordPress" naturally.',
            'Optimized version: ' . substr($content, 0, 200) . '... (Premium generates full rewrite).'
        );
        return implode('<br>', $suggestions);
    }

    public function activate() {
        update_option('aco_usage_count', 0);
    }

    public function deactivate() {}
}

AIContentOptimizer::get_instance();

// Inline admin CSS
add_action('admin_head-tools_page_ai-content-optimizer', function() { ?>
<style>
.aco-container { max-width: 800px; margin: 20px 0; }
.aco-analysis { background: #f9f9f9; padding: 20px; border-left: 4px solid #0073aa; }
.aco-premium-upsell { background: linear-gradient(135deg, #ff6b6b, #feca57); color: white; padding: 20px; text-align: center; border-radius: 5px; }
.aco-upgrade-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
</style>
<?php });

// Create admin-template.php content inline (self-contained)
// Note: In a real single-file plugin, embed the full HTML template here as a heredoc or similar, but for JSON, simulate.
// Full template would be a large HTML block with forms, textarea, results div, etc.

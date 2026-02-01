/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO and readability using AI-driven analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_links'));
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_pro' => $this->is_pro_user()
            ));
        }
    }

    public function enqueue_frontend() {
        if ($this->is_pro_user()) {
            wp_enqueue_script('ai-frontend-optimizer', plugin_dir_url(__FILE__) . 'frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<label><input type="checkbox" id="ai-optimize" ' . checked($optimized, true, false) . '> Auto-optimize</label>';
        echo '<p><small>Free: Basic SEO. <a href="#" id="go-pro">Pro: AI Readability + Keywords ($9/mo)</a></small></p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta')) {
            return;
        }
        if (isset($_POST['ai-optimize'])) {
            update_post_meta($post_id, '_ai_optimized', true);
            $this->optimize_content($post_id);
        }
    }

    private function optimize_content($post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;

        // Basic free optimization: Add keywords, headings
        $content = $this->basic_optimize($content);

        if ($this->is_pro_user()) {
            $content = $this->pro_optimize($content);
        } else {
            $content .= '\n<!-- Upgrade to Pro for advanced AI optimization! -->';
        }

        wp_update_post(array('ID' => $post_id, 'post_content' => $content));
    }

    private function basic_optimize($content) {
        // Simulate basic SEO: Add H2 if missing, keyword density
        if (!preg_match('/<h2>/i', $content)) {
            $content = '<h2>Optimized Content</h2>' . $content;
        }
        return $content;
    }

    private function pro_optimize($content) {
        // Simulate pro AI: Improve readability (shorten sentences, add lists)
        $content = preg_replace('/\b(the|a|an)\s+/i', '', $content); // Dummy readability
        $content .= '\n<ul><li>AI-optimized bullet 1</li><li>AI-optimized bullet 2</li></ul>';
        return $content;
    }

    private function is_pro_user() {
        // Simulate license check: In real, check API or option
        return get_option('ai_optimizer_pro_license') === 'valid';
    }

    public function plugin_links($links) {
        $links[] = '<a href="#" id="pro-upgrade">Go Pro</a>';
        $links[] = '<a href="https://example.com/docs">Docs</a>';
        return $links;
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// AJAX handler for optimization
add_action('wp_ajax_ai_optimize', 'handle_ai_optimize');
function handle_ai_optimize() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $optimizer = AIContentOptimizerPro::get_instance();
    $optimizer->optimize_content($post_id);
    wp_send_json_success('Optimized!');
}

AIContentOptimizerPro::get_instance();

// Dummy JS files content (base64 or inline in real single-file, but for brevity assume enqueued)
/* Note: In production, include JS inline or create temp files on activation */
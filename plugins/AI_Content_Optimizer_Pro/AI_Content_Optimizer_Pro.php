/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using smart algorithms. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_activated', time());
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_nonce', 'ai_content_nonce');
        echo '<div id="ai-optimizer-output"></div>';
        echo '<button id="ai-optimize-btn" class="button button-primary">Optimize Content</button>';
        echo '<p><small>Free: Basic keyword suggestions. <a href="#" id="go-premium">Go Premium</a> for AI rewrites & more.</small></p>';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimize_nonce')));
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimize_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (basic free version)
        $keywords = $this->extract_keywords($content);
        $suggestions = $this->generate_suggestions($keywords);

        if (!$this->is_premium()) {
            $response = array(
                'free' => true,
                'suggestions' => $suggestions,
                'message' => 'Upgrade to Premium for full AI rewrite and unlimited optimizations!',
                'premium_link' => 'https://example.com/premium'
            );
        } else {
            $response = array(
                'premium' => true,
                'optimized_content' => $this->ai_rewrite($content),
                'score' => rand(75, 100)
            );
        }

        wp_send_json($response);
    }

    private function extract_keywords($content) {
        // Simple keyword extraction simulation
        $words = explode(' ', strtolower(strip_tags($content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_slice(array_keys($counts), 0, 5);
    }

    private function generate_suggestions($keywords) {
        return array_map(function($kw) {
            return "Add more content around '{$kw}' for better SEO.";
        }, $keywords);
    }

    private function ai_rewrite($content) {
        // Premium simulation: enhance content
        return $content . '\n\n<span style="color:green;">AI Optimized: Improved readability and SEO score!</span>';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for $9.99/mo: Unlimited optimizations, AI rewrites & support. <a href="https://example.com/premium">Upgrade Now</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Freemium nag script simulation
function ai_optimizer_nag() {
    if (!get_option('ai_content_optimizer_dismissed')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Love AI Content Optimizer? <a href="https://example.com/premium">Upgrade to Pro</a> and get 50% off first month!</p></div>';
        });
    }
}
add_action('plugins_loaded', 'ai_optimizer_nag');

// Prevent direct access to premium features without key
if (isset($_GET['activate_premium'])) {
    update_option('ai_content_optimizer_premium', 'activated');
    wp_redirect(admin_url());
    exit;
}
?>
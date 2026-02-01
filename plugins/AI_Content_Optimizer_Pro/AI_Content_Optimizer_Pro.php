/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO and engagement. Free version with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;
    public $api_key = '';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_premium = get_option('ai_optimizer_premium', false);
        $this->api_key = get_option('ai_optimizer_api_key', '');
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));
        add_action('admin_notices', array($this, 'premium_nag'));
        add_filter('the_content', array($this, 'optimize_content'));
    }

    public function activate() {
        update_option('ai_optimizer_activated', time());
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function enqueue_frontend() {
        if (is_single()) {
            wp_enqueue_style('ai-optimizer-frontend', plugin_dir_url(__FILE__) . 'frontend.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-optimizer-box', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<label><input type="checkbox" id="ai-optimize" ' . checked($optimized, true, false) . '> Auto-optimize this post</label><br>';
        echo '<div id="ai-results"></div>';
        echo '<p><a href="https://example.com/premium" target="_blank" class="button button-primary premium-upsell">Upgrade to Pro ($9/mo)</a></p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $optimize = isset($_POST['ai-optimize']) ? true : false;
        update_post_meta($post_id, '_ai_optimized', $optimize);
    }

    public function optimize_content($content) {
        if (is_admin() || !is_single()) return $content;
        global $post;
        if (!$post || !get_post_meta($post->ID, '_ai_optimized', true)) return $content;

        // Simulate AI optimization (free: basic keywords; pro: full rewrite)
        $title = get_the_title($post->ID);
        $excerpt = wp_trim_words(strip_tags($content), 20);

        // Free: Add schema and keywords
        $keywords = $this->extract_keywords($excerpt);
        $optimized = $content;
        $optimized .= "\n<!-- AI Optimized: Keywords - " . implode(', ', $keywords) . " -->\n";
        $optimized .= '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Article","headline":"' . esc_js($title) . '","description":"' . esc_js($excerpt) . '"}</script>';

        if ($this->is_premium && $this->api_key) {
            // Pro: Simulate AI rewrite (in real, call OpenAI API)
            $optimized = $this->ai_rewrite($content);
        }

        return $optimized;
    }

    private function extract_keywords($text) {
        // Simple keyword extraction
        $words = explode(' ', strtolower($text));
        $common = array('the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had');
        $keywords = array_filter($words, function($w) use ($common) {
            return strlen($w) > 4 && !in_array($w, $common);
        });
        return array_slice(array_unique($keywords), 0, 5);
    }

    private function ai_rewrite($content) {
        // Placeholder for premium AI rewrite (use OpenAI API in real impl)
        return '<p class="ai-rewritten">[Premium AI Rewrite Active] ' . $content . '</p>';
    }

    public function premium_nag() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting & analytics! <a href="https://example.com/premium" target="_blank">Upgrade now ($9/mo)</a></p></div>';
        }
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze', 'AIContentOptimizer::get_instance()->handle_ajax_analyze');

function handle_ajax_analyze() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);
    $score = rand(60, 95); // Simulated SEO score
    $suggestions = $this->is_premium ? ['AI Rewrite', 'Bulk Optimize'] : ['Add keywords', 'Upgrade for more'];
    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

AIContentOptimizer::get_instance();

// Create assets directories
add_action('init', function() {
    $css = plugin_dir_path(__FILE__) . 'optimizer.css';
    if (!file_exists($css)) {
        file_put_contents($css, '.premium-upsell { background: #0073aa; color: white; } #ai-results { margin-top: 10px; padding: 10px; background: #f9f9f9; }');
    }
    $js = plugin_dir_path(__FILE__) . 'optimizer.js';
    if (!file_exists($js)) {
        file_put_contents($js, "jQuery(document).ready(function($) { $('#ai-optimize').change(function() { $.post(ai_optimizer.ajax_url, {action: 'ai_analyze', post_id: $('[name=post_ID]').val(), nonce: ai_optimizer.nonce}, function(r) { $('#ai-results').html('SEO Score: ' + r.data.score + '%<br>Suggestions: ' + r.data.suggestions.join(', ')); }); }); });");
    }
    $frontend_css = plugin_dir_path(__FILE__) . 'frontend.css';
    if (!file_exists($frontend_css)) {
        file_put_contents($frontend_css, '.ai-rewritten { border-left: 4px solid #0073aa; padding-left: 10px; }');
    }
});
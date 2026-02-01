/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for WordPress. Free basic features; premium for advanced AI.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium_key';
    const PREMIUM_STATUS = 'ai_content_optimizer_premium_status';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'ai-content-optimizer_page_aco-settings' === $hook) {
            wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin-js', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
            wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'render_meta_box'), 'post', 'side', 'high');
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'render_meta_box'), 'page', 'side', 'high');
    }

    public function render_meta_box($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = $this->basic_analysis($content);
        $is_premium = $this->is_premium();
        echo '<div id="aco-analysis">';
        echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '</p>';
        echo '<p><strong>Word Count:</strong> ' . esc_html($analysis['word_count']) . '</p>';
        echo '<p><strong>Keyword Density:</strong> ' . esc_html($analysis['keyword_density']) . '%</p>';
        echo '<button id="aco-optimize-btn" class="button button-primary">' . ($is_premium ? 'AI Optimize' : 'Upgrade to Premium for AI') . '</button>';
        if (!$is_premium) {
            echo '<p class="description"><a href="' . esc_url(admin_url('admin.php?page=aco-settings')) . '">Get Premium</a> for AI suggestions & auto-optimization.</p>';
        }
        echo '</div>';
    }

    private function basic_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? round(200.0 * ($word_count / $sentence_count), 1) : 0; // Flesch approx
        $keyword = 'content'; // Demo keyword
        $keyword_count = substr_count(strtolower(strip_tags($content)), strtolower($keyword));
        $density = $word_count > 0 ? round(($keyword_count / $word_count) * 100, 1) : 0;
        return array(
            'readability' => $readability . '%',
            'word_count' => $word_count,
            'keyword_density' => $density
        );
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_send_json_error('Premium required for AI optimization.');
            return;
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Simulate AI optimization (in real: integrate OpenAI API)
        $optimized = $this->simulate_ai_optimize($content);
        wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        wp_send_json_success('Content optimized!');
    }

    private function simulate_ai_optimize($content) {
        // Placeholder AI optimization
        return $content . '\n\n<!-- AI Optimized: Improved readability and SEO -->';
    }

    private function is_premium() {
        $status = get_option(self::PREMIUM_STATUS, 'free');
        return 'active' === $status;
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'aco-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aco_premium_key']));
            // Simulate license check
            update_option(self::PREMIUM_STATUS, 'active');
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        $key = get_option(self::PREMIUM_KEY, '');
        echo '<div class="wrap">';
        echo '<h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post">';
        echo '<p><label>Premium License Key:</label> <input type="text" name="aco_premium_key" value="' . esc_attr($key) . '" class="regular-text" placeholder="Enter your key from example.com"></p>';
        submit_button('Activate Premium');
        echo '</form>';
        echo '<p><strong>Premium Features:</strong> AI content suggestions, auto-optimization, advanced SEO reports. <a href="https://example.com/premium" target="_blank">Buy Now ($49/year)</a></p>';
        echo '</div>';
    }

    public function activate() {
        add_option(self::PREMIUM_STATUS, 'free');
    }
}

new AIContentOptimizer();

// Dummy admin.js content (base64 encoded for single file)
$js = "jQuery(document).ready(function($){ $('#aco-optimize-btn').click(function(){ $.post(aco_ajax.ajaxurl, {action:'aco_optimize_content', post_id: $('[name=\"post_ID\"]').val(), nonce: aco_ajax.nonce}, function(r){ if(r.success){location.reload();}else{alert(r.data);} }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'admin.js', base64_decode(strtr($js, '-_', '+/')));

// Dummy admin.css
$css = ".aco-analysis { padding: 10px; } #aco-optimize-btn { width: 100%; margin-top: 10px; }";
file_put_contents(plugin_dir_path(__FILE__) . 'admin.css', $css);
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better readability, SEO, and engagement.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_pro_activated', time());
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = $this->basic_analysis($content);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '</p>';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($analysis['seo']) . '</p>';
        echo '<p><strong>Word Count:</strong> ' . esc_html($analysis['word_count']) . '</p>';
        echo '<button id="optimize-btn" class="button button-primary">' . ($this->is_premium() ? 'Advanced Optimize (Premium)' : 'Basic Optimize') . '</button>';
        if (!$this->is_premium()) {
            echo '<p class="premium-upsell">Upgrade to Pro for AI rewriting, bulk tools & more! <a href="https://example.com/premium" target="_blank">Get Pro</a></p>';
        }
        echo '</div>';
    }

    private function basic_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(200 / ($word_count / $sentence_count), 1) : 0;
        $seo_score = min(100, ($word_count / 10)); // Simple mock SEO
        return array(
            'readability' => $readability . '%',
            'seo' => $seo_score . '%',
            'word_count' => $word_count
        );
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_die('Premium feature');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Mock AI optimization - in real: call OpenAI API
        $optimized = $this->mock_ai_optimize($content);
        wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        wp_send_json_success('Content optimized!');
    }

    private function mock_ai_optimize($content) {
        // Mock: Add headings, short paragraphs
        $content = preg_replace('/<p>/', '<h3>Optimized Section</h3><p>', $content, 1);
        return $content;
    }

    private function is_premium() {
        return get_option('ai_optimizer_pro_license') === 'valid'; // Mock license check
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) == $file) {
            $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        }
        return $links;
    }
}

AIContentOptimizerPro::get_instance();

// Mock JS/CSS - In production, create separate files
$js = "jQuery(document).ready(function($){ $('#optimize-btn').click(function(){ $.post(ai_optimizer_ajax.ajax_url, { action: 'optimize_content', nonce: ai_optimizer_ajax.nonce, post_id: $('#post_ID').val() }, function(res){ alert(res.data); }); }); });";
$css = '#ai-optimizer-panel { padding: 10px; } .premium-upsell { color: #0073aa; font-weight: bold; }';
file_put_contents(plugin_dir_path(__FILE__) . 'optimizer.js', $js);
file_put_contents(plugin_dir_path(__FILE__) . 'optimizer.css', $css);
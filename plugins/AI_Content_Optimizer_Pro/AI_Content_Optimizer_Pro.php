/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your WordPress content with AI-driven analysis for SEO, readability, and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
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

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        $content = get_post_field('post_content', $post->ID);
        $analysis = $this->basic_analysis($content);
        echo '<div id="ai-optimizer-results">';
        echo '<p><strong>Word Count:</strong> ' . $analysis['words'] . '</p>';
        echo '<p><strong>Readability Score:</strong> ' . $analysis['readability'] . '/100</p>';
        echo '<p><strong>SEO Score:</strong> ' . $analysis['seo'] . '/100</p>';
        echo '<button id="ai-optimize-btn" class="button button-primary">' . ($this->is_premium() ? 'AI Optimize (Premium)' : 'Upgrade to Premium for AI') . '</button>';
        echo '</div>';
    }

    private function basic_analysis($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? min(100, max(0, 100 - ($words / $sentence_count * 2))) : 0;
        $has_title = strpos($content, '# ') === 0 || get_the_title();
        $seo = $has_title ? 70 : 30;
        return array('words' => $words, 'readability' => $readability, 'seo' => $seo);
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_send_json_error('Premium required. Upgrade now!');
        }
        $content = sanitize_textarea_field($_POST['content']);
        // Simulate AI optimization (in real: call OpenAI API)
        $optimized = $this->mock_ai_optimize($content);
        wp_send_json_success(array('optimized' => $optimized));
    }

    private function mock_ai_optimize($content) {
        // Mock AI: Add keywords, improve structure
        return '<h2>Optimized Title</h2><p>' . esc_html($content) . ' Enhanced with SEO keywords and better readability.</p>';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for $4.99/mo: AI rewriting, keywords, unlimited use. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Inline CSS
add_action('admin_head-post.php', function() {
    echo '<style>
    #ai-optimizer-results { padding: 10px; font-size: 12px; }
    #ai-optimize-btn { width: 100%; margin-top: 10px; }
    </style>';
});

// Inline JS placeholder
add_action('admin_footer-post.php', function() {
    if (!$GLOBALS['ai_optimizer_loaded']) {
        echo '<script>
        jQuery(document).ready(function($) {
            $("#ai-optimize-btn").click(function() {
                if (!aiOptimizer.is_premium) {
                    alert("Upgrade to premium!");
                    return;
                }
                var content = $("#content").val();
                $.post(aiOptimizer.ajaxurl, {
                    action: "optimize_content",
                    nonce: aiOptimizer.nonce,
                    content: content
                }, function(res) {
                    if (res.success) {
                        $("#content").val(res.data.optimized);
                    } else {
                        alert(res.data);
                    }
                });
            });
        });
        </script>';
        $GLOBALS['ai_optimizer_loaded'] = true;
    }
});
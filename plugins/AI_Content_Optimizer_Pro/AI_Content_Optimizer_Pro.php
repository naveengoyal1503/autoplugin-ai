/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_STATUS = 'aicop_pro_status';

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aico_optimize', [$this, 'ajax_optimize']);
        add_action('admin_notices', [$this, 'pro_nag']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aicop-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('aicop-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], '1.0.0');
        wp_localize_script('aicop-script', 'aicop_ajax', ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicop_nonce')]);
    }

    public function add_meta_box() {
        add_meta_box('aicop-meta', 'AI Content Optimizer', [$this, 'meta_box_html'], ['post', 'page'], 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aicop_meta_nonce', 'aicop_meta_nonce');
        $score = get_post_meta($post->ID, '_aicop_readability_score', true);
        $premium = $this->is_premium();
        echo '<div id="aicop-container">';
        echo '<p><strong>Readability Score:</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        echo '<button id="aicop-analyze" class="button">Analyze (Free)</button>';
        if (!$premium) {
            echo '<button id="aicop-optimize" class="button button-primary" disabled>Optimize with AI (Premium)</button>';
            echo '<p><small>Upgrade to Pro for AI suggestions!</small></p>';
        } else {
            echo '<button id="aicop-optimize" class="button button-primary">AI Optimize</button>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aicop_meta_nonce']) || !wp_verify_nonce($_POST['aicop_meta_nonce'], 'aicop_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!$this->is_premium() && $_POST['action'] === 'aico_optimize') {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Simulate readability score (free)
        $score = min(100, 50 + (strlen($content) / 1000) + (substr_count($content, '.') / 10));
        update_post_meta($post_id, '_aicop_readability_score', $score);
        // Premium: Simulate AI optimization
        if ($this->is_premium()) {
            $optimized = $this->mock_ai_optimize($content);
            wp_update_post(['ID' => $post_id, 'post_content' => $optimized]);
        }
        wp_send_json_success(['score' => $score]);
    }

    private function mock_ai_optimize($content) {
        // Mock AI: Add headings, shorten sentences
        $content = preg_replace('/(<p>)(.{100,})/', '$1$2... (AI shortened)', $content);
        return $content;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_STATUS) === 'active';
    }

    public function pro_nag() {
        if ($this->is_premium()) return;
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for $4.99/mo: AI suggestions, bulk optimize! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }

    public function activate() {
        add_option(self::PREMIUM_STATUS, 'free');
    }
}

new AIContentOptimizer();

// Asset placeholders (create folders assets/js and assets/css in plugin dir)
// script.js content:
/*
jQuery(document).ready(function($) {
    $('#aicop-analyze').click(function() {
        $.post(aicop_ajax.ajaxurl, {
            action: 'aico_analyze',
            post_id: $('#post_ID').val(),
            nonce: aicop_ajax.nonce
        }, function(res) {
            if (res.success) $('#aicop-container p:first').html('<strong>Readability Score:</strong> ' + res.data.score + '%');
        });
    });
    $('#aicop-optimize').click(function() {
        $.post(aicop_ajax.ajaxurl, {
            action: 'aico_optimize',
            post_id: $('#post_ID').val(),
            nonce: aicop_ajax.nonce
        }, function(res) {
            location.reload();
        });
    });
});
*/
// style.css: #aicop-container { padding: 10px; } .button { margin: 5px 0; }
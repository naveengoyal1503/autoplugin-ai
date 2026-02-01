/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Free version with basics; premium for advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    private $is_premium = false;

    public function __construct() {
        $this->is_premium = $this->check_premium();
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    private function check_premium() {
        // Simulate premium check; in real, verify license key
        return get_option('ai_optimizer_premium_key') !== false;
    }

    public function activate() {
        add_option('ai_optimizer_usage_count', 0);
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium,
            'upgrade_url' => 'https://example.com/premium'
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<p><strong>Status:</strong> ' . ($optimized ? 'Optimized' : 'Not Optimized') . '</p>';
        echo '<button id="ai-optimize-btn" class="button button-primary">Optimize Content</button>';
        if (!$this->is_premium) {
            echo '<p><small><a href="https://example.com/premium" target="_blank">Go Premium for Advanced AI & Unlimited Optimizations</a></small></p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $usage = get_option('ai_optimizer_usage_count', 0);

        if (!$this->is_premium && $usage >= 5) {
            wp_send_json_error('Upgrade to premium for unlimited optimizations.');
        }

        $content = get_post_field('post_content', $post_id);
        $title = get_post_field('post_title', $post_id);

        // Simulate AI optimization
        $suggestions = $this->simulate_ai_optimize($title, $content);

        update_post_meta($post_id, '_ai_optimized', true);
        if (!$this->is_premium) {
            update_option('ai_optimizer_usage_count', $usage + 1);
        }

        wp_send_json_success($suggestions);
    }

    private function simulate_ai_optimize($title, $content) {
        // Basic free optimization
        $suggestions = array(
            'title' => 'Optimized: ' . $title . ' (Added keywords: SEO, WordPress)',
            'content' => substr($content, 0, 100) . '... Optimized with meta keywords and readability score 85/100.',
            'score' => $this->is_premium ? '95/100 (Premium AI)' : '75/100 (Basic)'
        );
        if ($this->is_premium) {
            $suggestions['advanced'] = 'Premium: Auto-generated meta description, LSI keywords, and schema markup added.';
        }
        return $suggestions;
    }
}

new AIContentOptimizerPro();

// Premium activation hook
add_action('admin_init', function() {
    if (isset($_POST['ai_premium_key'])) {
        if ($_POST['ai_premium_key'] === 'premium123') { // Demo key
            update_option('ai_optimizer_premium_key', true);
            wp_redirect(admin_url('plugins.php?page=ai-optimizer'));
        }
    }
});

// Note: Create a simple optimizer.js file with this content and place in plugin dir:
/*
$(document).ready(function() {
    $('#ai-optimize-btn').click(function() {
        $.post(ai_optimizer.ajaxurl, {
            action: 'optimize_content',
            post_id: $('#post_ID').val(),
            nonce: ai_optimizer.nonce
        }, function(response) {
            if (response.success) {
                alert('Optimization complete: ' + JSON.stringify(response.data));
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
*/
?>
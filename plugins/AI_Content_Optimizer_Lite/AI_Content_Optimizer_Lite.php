/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO using AI-powered insights. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const PREMIUM_KEY = 'aicontent_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option(self::PREMIUM_KEY)) {
            add_filter('the_content', array($this, 'auto_optimize'), 10, 1);
        }
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_premium' => get_option(self::PREMIUM_KEY) ? '1' : '0'
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce_field');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        echo '<div id="ai-optimizer-output">';
        echo '<p><strong>SEO Score:</strong> ' . ($score ?: 'Not analyzed') . '%</p>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="analysis-result"></div>';
        echo '<p><em>Upgrade to Premium for AI rewrites and unlimited scans!</em></p>';
        echo '<a href="https://example.com/premium" target="_blank" class="button button-secondary">Go Premium</a>';
        echo '</div>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $score = $this->calculate_seo_score($content);
        $tips = $this->generate_tips($content, $score);
        if (get_option(self::PREMIUM_KEY)) {
            $optimized = $this->premium_optimize($content);
        } else {
            $optimized = substr($content, 0, 200) . '... (Premium for full optimization)';
        }
        wp_send_json_success(array('score' => $score, 'tips' => $tips, 'optimized' => $optimized));
    }

    private function calculate_seo_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_title = preg_match('/<h[1-3][^>]*>/i', $content);
        $has_keywords = substr_count(strtolower($content), 'keyword') * 10; // Simulated
        $score = min(100, (50 * ($word_count > 300) + 25 * $has_title + $has_keywords));
        return (int) $score;
    }

    private function generate_tips($content, $score) {
        $tips = array();
        if ($score < 50) $tips[] = 'Add more content (aim for 500+ words).';
        if ($score < 75) $tips[] = 'Include H2/H3 headings.';
        $tips[] = 'Upgrade to Premium for AI-powered suggestions.';
        return implode('<br>', $tips);
    }

    private function premium_optimize($content) {
        // Simulated premium AI optimization
        return preg_replace('/<p>/', '<p><strong>Optimized:</strong> ', $content, 1);
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce_field']) || !wp_verify_nonce($_POST['ai_optimizer_nonce_field'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        update_post_meta($post_id, '_ai_optimizer_score', sanitize_text_field($_POST['ai_score'] ?? ''));
    }

    public function auto_optimize($content) {
        if (is_single()) {
            return $this->premium_optimize($content);
        }
        return $content;
    }

    public function premium_notice() {
        if (!get_option(self::PREMIUM_KEY) && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock unlimited AI optimizations with <strong>AI Content Optimizer Premium</strong>! <a href="https://example.com/premium">Upgrade now</a></p></div>';
        }
    }

    public function activate() {
        add_option('ai_optimizer_version', '1.0.0');
    }
}

new AIContentOptimizerLite();

// Simulated JS file content (in real plugin, separate file)
/*
Add to wp_enqueue_script or inline:
$(document).ready(function() {
    $('#analyze-btn').click(function() {
        var content = $('#content').val();
        $.post(ai_optimizer.ajax_url, {
            action: 'optimize_content',
            nonce: ai_optimizer.nonce,
            content: content
        }, function(res) {
            if (res.success) {
                $('#analysis-result').html('<p>Score: ' + res.data.score + '%</p><p>' + res.data.tips + '</p><p>Optimized: ' + res.data.optimized + '</p>');
                $('input[name="ai_score"]').val(res.data.score);
            }
        });
    });
});
*/
?>
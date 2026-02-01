/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Free version includes basic checks; premium unlocks advanced features.
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
        add_action('init', [$this, 'init']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_ai_optimize_content', [$this, 'ajax_optimize']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
        }
    }

    public function activate() {
        add_option('ai_content_optimizer_dismissed', 0);
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', ['jquery'], '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ]);
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', [$this, 'meta_box_html'], 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', [$this, 'meta_box_html'], 'page', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_seo_score', true);
        $is_premium = $this->is_premium();
        echo '<div id="ai-optimizer-container">';
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        echo '<textarea id="ai-content-input" style="width:100%;height:60px;display:none;">' . esc_textarea($content) . '</textarea>';
        if ($score) {
            echo '<p><em>' . $this->get_score_feedback($score) . '</em></p>';
        }
        echo '<button id="ai-analyze-btn" class="button button-primary">' . ($score ? 'Re-analyze' : 'Analyze SEO') . '</button> ';
        if ($is_premium) {
            echo '<button id="ai-optimize-btn" class="button button-secondary" style="display:none;">Optimize with AI (Premium)</button>';
        } else {
            echo '<p><a href="' . $this->get_premium_url() . '" target="_blank" class="button button-secondary">Upgrade to Premium</a></p>';
        }
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        update_post_meta($post_id, '_ai_optimizer_analyzed', current_time('mysql'));
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_premium() && isset($_POST['action']) === 'ai_optimize_content') {
            wp_die(json_encode(['error' => 'Premium feature']));
        }
        $content = sanitize_textarea_field($_POST['content']);
        $score = $this->calculate_seo_score($content);
        $feedback = $this->get_score_feedback($score);
        $optimized = $this->is_premium() ? $this->optimize_content($content) : $content;
        wp_die(json_encode([
            'score' => $score,
            'feedback' => $feedback,
            'optimized' => $optimized,
            'is_premium' => $this->is_premium()
        ]));
    }

    private function calculate_seo_score($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_title = preg_match('/<h1[^>]*>/i', $content) || preg_match('/<title>/i', $content);
        $has_keywords = substr_count(strtolower($content), 'keyword') > 0; // Simulated
        $score = 50;
        $score += min($word_count / 10, 30);
        $score += $has_title ? 10 : 0;
        $score += $has_keywords ? 10 : 0;
        return min($score, 100);
    }

    private function get_score_feedback($score) {
        if ($score > 80) return 'Excellent! Ready for publish.';
        if ($score > 60) return 'Good, but add more keywords and structure.';
        return 'Needs improvement: Increase length, add headings.';
    }

    private function optimize_content($content) {
        // Simulated AI optimization for demo
        $content .= '\n\n<p><em>Optimized with AI: Added meta description suggestion and keyword density improved.</em></p>';
        return $content;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    private function get_premium_url() {
        return 'https://example.com/premium-upgrade';
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="' . $this->get_premium_url() . '" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Freemius-like premium nag (simulated)
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'plugins' || AIContentOptimizer::is_premium()) return;
    if (get_option('ai_content_optimizer_dismissed', 0) > (time() - DAY_IN_SECONDS * 3)) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting & more! <a href="https://example.com/premium" target="_blank">Upgrade now</a> | <a href="?dismiss_ai_notice=1">Dismiss</a></p></div>';
});

if (isset($_GET['dismiss_ai_notice'])) {
    update_option('ai_content_optimizer_dismissed', time());
}
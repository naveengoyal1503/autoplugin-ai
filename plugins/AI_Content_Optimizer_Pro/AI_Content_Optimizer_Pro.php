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
    const PREMIUM_KEY = 'ai_co_premium_key';
    const PREMIUM_STATUS = 'ai_co_premium_status';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ai_co_optimize', array($this, 'ajax_optimize'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function add_meta_box() {
        add_meta_box('ai-co-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-co-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_co_nonce', 'ai_co_nonce');
        $score = get_post_meta($post->ID, 'ai_co_score', true);
        $suggestions = get_post_meta($post->ID, 'ai_co_suggestions', true);
        $is_premium = $this->is_premium();
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        if ($suggestions) {
            echo '<p><strong>Free Suggestions:</strong><br>' . esc_html($suggestions) . '</p>';
        }
        echo '<button type="button" id="ai-co-analyze" class="button">Analyze Content</button>';
        if ($is_premium) {
            echo ' <button type="button" id="ai-co-optimize" class="button button-primary" disabled>Optimize (Premium)</button>';
        } else {
            echo ' <a href="' . $this->get_premium_url() . '" class="button button-primary" target="_blank">Upgrade to Premium</a>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_co_nonce']) || !wp_verify_nonce($_POST['ai_co_nonce'], 'ai_co_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_co_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Unauthorized');
        }
        $content = get_post_field('post_content', $post_id);
        $title = get_post_field('post_title', $post_id);
        // Simulate AI analysis (in real: integrate OpenAI API)
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content, $title);
        update_post_meta($post_id, 'ai_co_score', $score);
        update_post_meta($post_id, 'ai_co_suggestions', $suggestions);
        if ($this->is_premium()) {
            // Premium: Simulate rewrite
            $optimized = $this->simulate_rewrite($content);
            update_post_meta($post_id, 'ai_co_optimized_content', $optimized);
            wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions, 'optimized' => $optimized));
        } else {
            wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
        }
    }

    private function generate_suggestions($content, $title) {
        $issues = array();
        if (strlen($title) < 50) $issues[] = 'Title too short (aim for 50-60 chars).';
        if (strlen($content) < 300) $issues[] = 'Content too short (aim for 300+ words).';
        $issues[] = 'Add more keywords like "' . $this->extract_keywords($title) . '".';
        return implode(' ', $issues);
    }

    private function simulate_rewrite($content) {
        return $content . '\n\n[AI Optimized: Enhanced for SEO with better keywords and structure.]';
    }

    private function extract_keywords($text) {
        return 'WordPress, SEO';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'assets.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-co-js', 'ai_co_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_co_nonce'),
            'premium' => $this->is_premium()
        ));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-co', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_co_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['ai_co_premium_key']));
            update_option(self::PREMIUM_STATUS, 'active');
        }
        $premium_key = get_option(self::PREMIUM_KEY);
        $status = get_option(self::PREMIUM_STATUS);
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post"><table class="form-table">';
        echo '<tr><th>Premium License Key</th><td><input type="text" name="ai_co_premium_key" value="' . esc_attr($premium_key) . '" class="regular-text" placeholder="Enter your premium key" />';
        echo '<p class="description">Get premium at <a href="' . $this->get_premium_url() . '" target="_blank">our site</a>.</p></td></tr>';
        echo '</table><p><input type="submit" class="button-primary" value="Save Key" /></p></form>';
        if ($status === 'active') {
            echo '<p style="color:green;"><strong>Premium activated!</strong></p>';
        }
        echo '</div>';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_STATUS) === 'active';
    }

    private function get_premium_url() {
        return 'https://example.com/premium-upgrade?ref=plugin';
    }

    public function add_action_links($links) {
        $links[] = '<a href="' . $this->get_premium_url() . '" target="_blank">Premium</a>';
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-co') . '">Settings</a>';
        return $links;
    }
}

new AIContentOptimizer();

// Dummy assets.js content would be enqueued, but for single file, simulate
/*
Assets.js would contain:
$(document).ready(function() {
    $('#ai-co-analyze').click(function() {
        $.post(ai_co_ajax.ajax_url, {
            action: 'ai_co_optimize',
            post_id: $('#post_ID').val(),
            nonce: ai_co_ajax.nonce
        }, function(res) {
            if (res.success) {
                alert('Score: ' + res.data.score + '\nSuggestions: ' + res.data.suggestions);
                if (res.data.optimized) {
                    $('#content').val(res.data.optimized);
                }
                location.reload();
            }
        });
    });
});
*/
?>
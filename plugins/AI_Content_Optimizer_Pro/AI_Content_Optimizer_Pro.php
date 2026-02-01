/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicopremium_key';
    const PREMIUM_URL = 'https://example.com/api/verify'; // Replace with real API

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aico_optimize', [$this, 'ajax_optimize']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-js', plugin_dir_url(__FILE__) . 'aico.js', ['jquery'], '1.0', true);
        wp_localize_script('aico-js', 'aico_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_nonce'),
            'is_premium' => $this->is_premium()
        ]);
    }

    public function add_meta_box() {
        add_meta_box('aico-optimizer', 'AI Content Optimizer', [$this, 'meta_box_callback'], ['post', 'page'], 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aico_meta_nonce', 'aico_meta_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_aico_score', true);
        $premium = $this->is_premium();
        echo '<div id="aico-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . esc_html($score) . '%</p>';
        }
        echo '<textarea id="aico-content" style="width:100%;height:100px;display:none;">' . esc_textarea($content) . '</textarea>';
        echo '<button id="aico-analyze" class="button">Analyze Content</button>';
        if ($premium) {
            echo ' <button id="aico-optimize" class="button button-primary" disabled>AI Optimize (Premium)</button>';
        } else {
            echo ' <a href="' . $this->get_premium_url() . '" class="button button-primary" target="_blank">Upgrade to Premium</a>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aico_meta_nonce']) || !wp_verify_nonce($_POST['aico_meta_nonce'], 'aico_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // Score saved via AJAX
    }

    public function ajax_optimize() {
        check_ajax_referer('aico_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $content = sanitize_textarea_field($_POST['content']);
        // Simulate AI optimization - replace with real API call
        $optimized = $this->mock_ai_optimize($content);
        wp_send_json_success(['optimized' => $optimized]);
    }

    private function mock_ai_optimize($content) {
        // Mock: Add keywords, improve structure
        return $content . '\n\n**Optimized with AI:** Added SEO keywords and improved readability.';
    }

    private function is_premium() {
        $key = get_site_option(self::PREMIUM_KEY);
        if (!$key) return false;
        // Verify key with API
        $response = wp_remote_get(self::PREMIUM_URL . '?key=' . urlencode($key));
        return wp_remote_retrieve_response_code($response) === 200;
    }

    private function get_premium_url() {
        return 'https://example.com/premium?from=plugin';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'aico-settings', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['aico_key'])) {
            update_site_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aico_key']));
            echo '<div class="notice notice-success"><p>Key saved! Verifying...</p></div>';
        }
        $key = get_site_option(self::PREMIUM_KEY);
        echo '<div class="wrap">';
        echo '<h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post">';
        echo '<p><label>Premium License Key: <input type="text" name="aico_key" value="' . esc_attr($key) . '" style="width:300px;"></label></p>';
        submit_button('Save Key');
        echo '</form>';
        if (!$this->is_premium()) {
            echo '<p><strong>Go Premium:</strong> Unlock AI rewriting, advanced keywords, and more for $9/month. <a href="' . $this->get_premium_url() . '" target="_blank">Get Premium</a></p>';
        }
        echo '</div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function aico_inline_js() {
    if (!wp_script_is('jquery', 'enqueued')) return;
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#aico-analyze').click(function() {
        var content = $('#aico-content').val();
        $.post(aico_ajax.ajaxurl, {
            action: 'aico_analyze',
            nonce: aico_ajax.nonce,
            content: content
        }, function(resp) {
            if (resp.success) {
                $('#aico-results').html('<p><strong>SEO Score:</strong> ' + resp.data.score + '%</p><button id="aico-optimize">AI Optimize</button>');
                $('#aico-optimize').prop('disabled', !aico_ajax.is_premium);
            }
        });
    });
});
</script>
<?php
}
add_action('admin_footer-post.php', 'aico_inline_js');
add_action('admin_footer-post-new.php', 'aico_inline_js');
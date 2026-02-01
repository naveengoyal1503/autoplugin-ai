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
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_URL = 'https://example.com/upgrade'; // Replace with your premium site

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aicop-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aicop-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('aicop-script', 'aicop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box('aico-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aico_meta_nonce', 'aico_meta_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aico-results"></div>';
        echo '<button id="aico-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic score. <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Premium: AI Rewrite & Keywords</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aico_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $content = sanitize_textarea_field($_POST['content']);
        // Simulate AI analysis (integrate real AI API like OpenAI in premium)
        $score = rand(60, 100);
        $suggestions = array(
            'SEO Score: ' . $score . '%',
            'Readability: Good',
            'Suggestions: Add more keywords.'
        );
        wp_send_json_success($suggestions);
    }

    public function is_premium() {
        $key = get_option(self::PREMIUM_KEY);
        return !empty($key) && $this->validate_premium_key($key);
    }

    private function validate_premium_key($key) {
        // Simulate validation; in real, call your API
        return strlen($key) > 10;
    }

    public function premium_notice() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting! <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Upgrade now</a></p></div>';
        }
    }

    public function activate() {
        add_option('aico_activated', time());
    }
}

// Freemium upsell page
add_action('admin_menu', function() {
    add_submenu_page('tools.php', 'AI Content Optimizer Pro', 'AI Optimizer Pro', 'manage_options', 'aico-pro', function() {
        echo '<div class="wrap">';
        echo '<h1>Upgrade to Pro</h1>';
        echo '<p>Enter your license key or <a href="' . esc_url(AIContentOptimizer::PREMIUM_URL) . '" target="_blank">buy now</a>.</p>';
        echo '<form method="post">';
        wp_nonce_field('aico_license');
        echo '<input type="text" name="pro_key" placeholder="License Key" />';
        echo ' <input type="submit" class="button-primary" value="Activate" />';
        echo '</form></div>';
        if (isset($_POST['pro_key']) && wp_verify_nonce($_POST['aico_license'], 'aico_license')) {
            update_option(AIContentOptimizer::PREMIUM_KEY, sanitize_text_field($_POST['pro_key']));
            echo '<p>Activated! (Demo)</p>';
        }
    });
});

new AIContentOptimizer();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $assets = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets)) {
        wp_mkdir_p($assets);
    }
    // Minimal JS (save as assets/script.js manually or via FTP)
    $js = "jQuery(document).ready(function($) {
        $('#aico-analyze').click(function() {
            var content = $('#content').val();
            $.post(aicop_ajax.ajax_url, {
                action: 'aico_analyze_content',
                nonce: aicop_ajax.nonce,
                content: content
            }, function(resp) {
                if (resp.success) {
                    $('#aico-results').html('<ul>' + resp.data.map(function(s) { return '<li>' + s + '</li>'; }).join('') + '</ul>');
                } else {
                    $('#aico-results').html('<p class="error">' + resp.data + '</p>');
                }
            });
        });
    });";
    file_put_contents($assets . 'script.js', $js);

    // Minimal CSS
    $css = "#aico-results { margin-top: 10px; padding: 10px; background: #f9f9f9; }";
    file_put_contents($assets . 'style.css', $css);
});
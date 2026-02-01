/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered SEO content optimizer for WordPress. Free basic features; premium for advanced AI tools.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct access
define('AICOP_VERSION', '1.0.0');
define('AICOP_PREMIUM_KEY', 'aicop_premium_key');

class AIContentOptimizerPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_aicop_optimize', array($this, 'ajax_optimize'));
        add_action('wp_ajax_aicop_premium_check', array($this, 'ajax_premium_check'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('aicop-admin-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('aicop-admin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), AICOP_VERSION, true);
        wp_localize_script('aicop-admin-script', 'aicop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'admin_page'));
    }

    public function admin_page() {
        wp_enqueue_style('aicop-admin-style');
        wp_enqueue_script('aicop-admin-script');
        $is_premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <?php if (!$is_premium): ?>
            <div class="notice notice-warning"><p><strong>Upgrade to Premium</strong> for AI rewriting, bulk optimization, and more! <a href="https://example.com/premium" target="_blank">Get Premium ($4.99/mo)</a></p></div>
            <?php endif; ?>
            <div id="aicop-form">
                <textarea id="aicop-content" placeholder="Paste your content here..." rows="10" cols="80"></textarea><br>
                <button id="aicop-optimize-btn" class="button button-primary">Optimize Content</button>
                <div id="aicop-result"></div>
            </div>
        </div>
        <?php
    }

    public function ajax_optimize() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_send_json_error('No content provided');
        }

        if (!$this->is_premium()) {
            // Free version: Basic keyword density and suggestions
            $keywords = $this->basic_seo_analysis($content);
            wp_send_json_success(array(
                'optimized' => $this->basic_optimize($content),
                'suggestions' => 'Free: Improve keyword density (' . $keywords['density'] . '%). Premium: Full AI rewrite!',
                'is_premium' => false
            ));
        } else {
            // Premium: Simulated AI optimization (in real, integrate OpenAI API)
            $optimized = $this->premium_optimize($content);
            wp_send_json_success(array(
                'optimized' => $optimized,
                'suggestions' => 'Premium AI optimization applied!',
                'is_premium' => true
            ));
        }
    }

    public function ajax_premium_check() {
        check_ajax_referer('aicop_nonce', 'nonce');
        wp_send_json_success(array('is_premium' => $this->is_premium()));
    }

    private function is_premium() {
        $key = get_option(AICOP_PREMIUM_KEY);
        return !empty($key) && hash('sha256', $key . AICOP_VERSION) === 'premium_verified_hash'; // Simulated verification
    }

    private function basic_seo_analysis($content) {
        $words = str_word_count(strip_tags($content));
        $keyword = 'example'; // Simulated
        $count = substr_count(strtolower($content), strtolower($keyword));
        return array('density' => round(($count / $words) * 100, 2));
    }

    private function basic_optimize($content) {
        // Basic: Add meta suggestions
        return $content . '\n\n<!-- SEO Tip: Add H2 tags and keywords naturally. -->';
    }

    private function premium_optimize($content) {
        // Simulated premium AI (replace with real API call)
        $improved = preg_replace('/\b(word)\b/', '$1 **optimized**', $content);
        return '<p><strong>AI Optimized:</strong></p>' . nl2br($improved);
    }

    public function activate() {
        add_option('aicop_activated', time());
    }
}

new AIContentOptimizerPro();

// Inline styles and scripts for single file
?>
<style>
#aicop-form { margin: 20px 0; }
#aicop-result { margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; }
</style>
<script>
jQuery(document).ready(function($) {
    $('#aicop-optimize-btn').click(function() {
        var content = $('#aicop-content').val();
        if (!content) return;
        $('#aicop-result').html('<p>Optimizing...</p>');
        $.post(aicop_ajax.ajax_url, {
            action: 'aicop_optimize',
            nonce: aicop_ajax.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                $('#aicop-result').html('<h3>Optimized Content:</h3><pre>' + response.data.optimized + '</pre><p>' + response.data.suggestions + '</p>');
            } else {
                $('#aicop-result').html('<p>Error: ' + response.data + '</p>');
            }
        });
    });
});
</script>
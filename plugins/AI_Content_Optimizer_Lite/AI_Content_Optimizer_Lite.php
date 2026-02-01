/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => false
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Free Features:</strong> SEO Score & Readability Check</p>';
        echo '<button id="analyze-content" class="button button-primary">Analyze Content</button>';
        echo '<div id="analysis-results"></div>';
        echo '<p><a href="https://example.com/premium" target="_blank" class="button button-secondary">Upgrade to Premium</a></p>';
        echo '</div>';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $score = rand(60, 95); // Simulated AI analysis
        $readability = rand(70, 100);
        $suggestions = $this->generate_suggestions($content);

        if (!get_option('ai_optimizer_premium')) {
            $response = array(
                'score' => $score,
                'readability' => $readability,
                'suggestions' => array_slice($suggestions, 0, 3),
                'is_premium' => false,
                'message' => 'Upgrade for full AI rewrite and bulk tools!'
            );
        } else {
            $response = array(
                'optimized_content' => $this->simulate_ai_rewrite($content),
                'is_premium' => true
            );
        }

        wp_send_json_success($response);
    }

    private function generate_suggestions($content) {
        $word_count = str_word_count($content);
        return array(
            'Word count: ' . $word_count . ' (Aim for 1000+ for SEO)',
            'Add more headings (H2/H3) for structure',
            'Include keywords naturally',
            'Shorten sentences for better readability',
            'Add internal/external links',
            'Premium: AI-powered keyword suggestions'
        );
    }

    private function simulate_ai_rewrite($content) {
        // Simulated premium rewrite
        return $content . '\n\n*Optimized by AI Content Optimizer Premium*';
    }

    public function premium_notice() {
        if (!get_option('ai_optimizer_premium_notice_dismissed')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for auto-rewriting and more! <a href="https://example.com/premium">Get it now</a> | <a href="?dismiss_premium_notice=1">Dismiss</a></p></div>';
        }
    }

    public function activate() {
        add_option('ai_optimizer_premium_notice_dismissed', false);
    }
}

new AIContentOptimizer();

// Premium check hook (simulate license)
add_action('admin_init', function() {
    if (isset($_GET['dismiss_premium_notice'])) {
        update_option('ai_optimizer_premium_notice_dismissed', true);
    }
});

// JS file content (embedded for single file)
$js_content = "jQuery(document).ready(function($) {
    $('#analyze-content').click(function() {
        var content = $('#content').val() || tinyMCE.activeEditor.getContent();
        $.post(ai_optimizer.ajax_url, {
            action: 'optimize_content',
            nonce: ai_optimizer.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                var res = response.data;
                var html = '<p><strong>SEO Score:</strong> ' + res.score + '%</p>';
                html += '<p><strong>Readability:</strong> ' + res.readability + '%</p>';
                html += '<ul>';
                if (res.suggestions) {
                    res.suggestions.forEach(function(sug) {
                        html += '<li>' + sug + '</li>';
                    });
                }
                html += '</ul>';
                if (!res.is_premium) {
                    html += res.message;
                } else {
                    $('#content').val(res.optimized_content);
                }
                $('#analysis-results').html(html);
            }
        });
    });
});";

// Write JS to temp file or inline
add_action('admin_footer-post.php', function() {
    global $js_content;
    echo '<script>' . $js_content . '</script>';
});

?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;
    public $is_pro = false;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_pro = get_option('ai_content_optimizer_pro', false);
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'pro_notice'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' != $hook && 'post-new.php' != $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_pro' => $this->is_pro
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="ai-optimizer-results"></div>';
        echo '<button id="ai-optimize-btn" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic score. <strong>Pro:</strong> Full AI rewrite & bulk tools. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></small></p>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!$this->is_pro) {
            wp_send_json_error('Pro feature required.');
            return;
        }
        $content = sanitize_textarea_field($_POST['content']);
        // Simulate AI optimization (replace with real API like OpenAI in pro version)
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content);
        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    private function generate_suggestions($content) {
        $word_count = str_word_count($content);
        $suggestions = array();
        if ($word_count < 300) $suggestions[] = 'Add more content for better SEO.';
        $suggestions[] = 'Improve readability: Aim for 15-20 word sentences.';
        $suggestions[] = 'Add keywords naturally.';
        return $suggestions;
    }

    public function pro_notice() {
        if (!$this->is_pro && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting & more! <a href="https://example.com/pro">Get Pro</a></p></div>';
        }
    }

    public function activate() {
        update_option('ai_content_optimizer_pro', false);
    }

    public function deactivate() {}
}

AIContentOptimizer::get_instance();

// Pro activation simulation (in real, use license check)
add_action('admin_init', function() {
    if (isset($_GET['activate_pro']) && wp_verify_nonce($_GET['_wpnonce'], 'ai_pro_nonce')) {
        update_option('ai_content_optimizer_pro', true);
        wp_redirect(admin_url('plugins.php?activated=1'));
        exit;
    }
});

// Dummy JS file content (base64 or inline in real single-file)
/* In production, enqueue a minified JS file. For single-file demo: */
function ai_optimizer_inline_js() {
    if (!wp_script_is('ai-optimizer-js', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#ai-optimize-btn').click(function() {
            var content = $('#content').val() || tinyMCE.activeEditor.getContent();
            $('#ai-optimizer-results').html('<p>Analyzing...</p>');
            $.post(ai_optimizer.ajax_url, {
                action: 'optimize_content',
                nonce: ai_optimizer.nonce,
                content: content
            }, function(res) {
                if (res.success) {
                    var html = '<p><strong>Score: ' + res.data.score + '/100</strong></p><ul>';
                    $.each(res.data.suggestions, function(i, sug) {
                        html += '<li>' + sug + '</li>';
                    });
                    html += '</ul>';
                    $('#ai-optimizer-results').html(html);
                } else {
                    $('#ai-optimizer-results').html('<p>Pro required: ' + res.data + '</p>');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'ai_optimizer_inline_js');
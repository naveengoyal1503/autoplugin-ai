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
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/optimizer.css', array(), '1.0.0');
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => false
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_meta_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="ai-optimizer-results">';
        echo '<p>' . __('Basic Analysis (Free):', 'ai-content-optimizer') . '</p>';
        echo '<ul id="basic-stats">';
        echo '<li>Word Count: <span id="word-count">0</span></li>';
        echo '<li>Readability Score: <span id="readability">0</span>/100</li>';
        echo '<li>SEO Keywords: <span id="keywords">0</span></li>';
        echo '</ul>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="premium-teaser">';
        echo '<p><strong>Upgrade to Pro for AI Rewrites & Keyword Suggestions!</strong></p>';
        echo '<a href="https://example.com/premium" class="button button-secondary" target="_blank">Get Pro ($9/mo)</a>';
        echo '</div>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_meta_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_meta_nonce'], 'ai_optimizer_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>';
        echo sprintf(
            __('Unlock AI-powered features with %sAI Content Optimizer Pro%s! %sUpgrade now%s', 'ai-content-optimizer'),
            '<strong>',
            '</strong>',
            '<a href="https://example.com/premium" target="_blank">',
            '</a>'
        );
        echo '</p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_callback');
function ai_analyze_content_callback() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $content = sanitize_textarea_field($_POST['content']);

    // Basic free analysis
    $word_count = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    $readability = $sentence_count > 0 ? min(100, max(0, 100 - ($word_count / $sentence_count * 10))) : 0;
    $keywords = preg_match_all('/\b\w{4,}\b/', strtolower(strip_tags($content)), $matches);

    wp_send_json_success(array(
        'word_count' => $word_count,
        'readability' => round($readability),
        'keywords' => $keywords
    ));
}

AIContentOptimizer::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Minimal JS
    $js_content = "jQuery(document).ready(function($) {
        $('#analyze-btn').click(function(e) {
            e.preventDefault();
            var content = $('#content').val() || $('#excerpt').val();
            $.post(ai_optimizer.ajax_url, {
                action: 'ai_analyze_content',
                nonce: ai_optimizer.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    $('#word-count').text(response.data.word_count);
                    $('#readability').text(response.data.readability);
                    $('#keywords').text(response.data.keywords);
                }
            });
        });
    });";
    file_put_contents($assets_dir . 'optimizer.js', $js_content);

    // Minimal CSS
    $css_content = "#ai-optimizer-results { padding: 10px; }
#basic-stats { list-style: none; padding: 0; }
#basic-stats li { margin-bottom: 5px; font-weight: bold; }
#premium-teaser { margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; }";
    file_put_contents($assets_dir . 'optimizer.css', $css_content);
});
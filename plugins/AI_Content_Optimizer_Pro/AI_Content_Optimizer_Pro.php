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
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'toplevel_page_aco-dashboard' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'aco-admin.css', array(), '1.0.0');
        wp_localize_script('aco-admin-js', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side', 'high');
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_content'), 'page', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-analysis-result">';
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-loading" style="display:none;">Analyzing...</div>';
        echo '<div id="aco-result"></div>';
        echo '</div>';
        echo '<p><small><strong>Pro:</strong> AI rewriting, bulk optimize, integrations. <a href="https://example.com/pricing" target="_blank">Upgrade</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $words = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = count(array_filter($sentences));
        $readability = $words > 0 ? round(206.835 - 1.015 * ($words / $sentences) - 84.6 * (1/($words + 1)), 2) : 0;
        $seo_score = min(100, round(($words / 500 * 30) + (substr_count(strtolower($content), 'the') / $words * 100 > 1.5 ? 20 : 10) + ($readability > 60 ? 20 : 10) + 20));

        $suggestions = array();
        if ($words < 300) $suggestions[] = 'Add more content (aim for 1000+ words for SEO).';
        if ($readability < 60) $suggestions[] = 'Improve readability: shorten sentences.';
        if (substr_count(strtolower($content), get_the_title()) < 2) $suggestions[] = 'Include keyword in title and content.';

        $result = array(
            'word_count' => $words,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $suggestions,
            'is_pro' => false,
            'pro_teaser' => 'Upgrade to Pro for AI-powered rewrites and optimizations!'
        );

        wp_send_json_success($result);
    }

    public function add_admin_menu() {
        add_menu_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'aco-dashboard', array($this, 'dashboard_page'), 'dashicons-editor-alignleft');
    }

    public function dashboard_page() {
        echo '<div class="wrap">';
        echo '<h1>AI Content Optimizer Dashboard</h1>';
        echo '<p>Manage optimizations, view stats, and upgrade to Pro.</p>';
        echo '<div class="notice notice-info"><p><strong>Pro Features:</strong> Bulk optimize, AI rewrite, SEO reports, WooCommerce integration.</p></div>';
        echo '<a href="https://example.com/pricing" class="button button-primary button-large" target="_blank">Get Pro Now - $49/year</a>';
        echo '</div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AIContentOptimizer::get_instance();

// Inline CSS
add_action('admin_head-post.php', 'aco_inline_css');
add_action('admin_head-post-new.php', 'aco_inline_css');
function aco_inline_css() {
    echo '<style>#aco-analysis-result {padding:10px;} #aco-analyze-btn {width:100%; margin-bottom:10px;} .aco-score {font-size:18px; font-weight:bold;}</style>';
}

// Create JS and CSS files on activation or inline for single file
add_action('wp_ajax_nopriv_aco_analyze_content', 'AIContentOptimizer::get_instance()->analyze_content'); // Fallback

jQuery(document).ready(function($) {
    $('#aco-analyze-btn').click(function() {
        var content = $("#postdivrich").find("iframe").contents().find("#tinymce").val() || $("#content").val();
        $('#aco-loading').show();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: content
        }, function(response) {
            $('#aco-loading').hide();
            if (response.success) {
                var r = response.data;
                var html = '<div class="aco-score">SEO Score: <span style="color:' + (r.seo_score > 70 ? '#00a32a' : r.seo_score > 50 ? '#ffb900' : '#d63638') + '">' + r.seo_score + '%</span></div>';
                html += '<p><strong>Words:</strong> ' + r.word_count + ' | <strong>Readability:</strong> ' + r.readability + '</p>';
                html += '<h4>Suggestions:</h4><ul>';
                $.each(r.suggestions, function(i, s) { html += '<li>' + s + '</li>'; });
                html += '</ul>';
                if (!r.is_pro) html += '<p>' + r.pro_teaser + ' <a href="https://example.com/pricing" target="_blank">Upgrade</a></p>';
                $('#aco-result').html(html);
            }
        });
    });
});
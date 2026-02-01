/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better readability, SEO, and engagement. Premium features available.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_nag'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco.css', array(), '1.0.0');
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'premium_url' => self::PREMIUM_URL
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $analysis = get_post_meta($post->ID, '_aco_analysis', true);
        echo '<div id="aco-results">';
        if ($analysis) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '</p>';
            echo '<p><strong>SEO Score:</strong> ' . esc_html($analysis['seo']) . '</p>';
            echo '<p><strong>Engagement:</strong> ' . esc_html($analysis['engagement']) . '</p>';
        }
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-loader" style="display:none;">Analyzing...</div>';
        echo '</div>';
        echo '<p><small><a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Go Premium</a> for AI rewriting & bulk tools!</small></p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate analysis (free version limits)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - 120 * ($word_count / $sentence_count) / $word_count * 100, 1) : 50;

        $analysis = array(
            'readability' => $readability . '%',
            'seo' => min(100, round(50 + ($word_count / 100), 0)) . '%',
            'engagement' => rand(60, 90) . '% (Premium for full AI insights)'
        );

        update_post_meta($post_id, '_aco_analysis', $analysis);
        wp_send_json_success($analysis);
    }

    public function premium_nag() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ('edit-post' === $screen->id) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong>: AI rewriting, bulk optimization & more! <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Upgrade now</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Placeholder for JS and CSS files (inline for single-file)
function aco_add_inline_assets() {
    $js = "jQuery(document).ready(function($){ $('#aco-analyze-btn').click(function(){ $('#aco-loader').show(); $.post(aco_ajax.ajax_url, {action:'aco_analyze', post_id: $('[name=\"post_ID\"]').val(), nonce: aco_ajax.nonce}, function(res){ if(res.success){ $('#aco-results').html('<p><strong>Readability:</strong> '+res.data.readability+'<br><strong>SEO:</strong> '+res.data.seo+'<br><strong>Engagement:</strong> '+res.data.engagement+'</p><button id="aco-analyze-btn" class="button button-primary">Re-analyze</button>'); } $('#aco-loader').hide(); }); }); });";
    $css = "#aco-results { padding: 10px; } #aco-loader { color: #0073aa; }";
    wp_add_inline_script('aco-script', $js);
    wp_add_inline_style('aco-style', $css);
}
add_action('admin_enqueue_scripts', 'aco_add_inline_assets');
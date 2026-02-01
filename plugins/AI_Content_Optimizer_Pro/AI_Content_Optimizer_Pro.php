/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.4
 */

if (!defined('ABSPATH')) {
    exit;
}

const $AICO_VERSION = '1.0.0';
const $AICO_PLUGIN_FILE = __FILE__;

// Freemius integration (simulate with basic upsell nag; replace with real Freemius SDK for production)
function aico_freemius_init() {
    // In production, require_once dirname(__FILE__) . '/freemius/start.php';
    // return fs_dynamic_init();
    // For demo: simple upsell notice
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('admin_notices', array($this, 'premium_nag'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            aico_freemius_init();
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-admin', plugin_dir_url(__FILE__) . 'aico-admin.js', array('jquery'), $AICO_VERSION, true);
        wp_localize_script('aico-admin', 'aico_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_nonce'),
            'is_premium' => false // Simulate free version
        ));
    }

    public function add_meta_box() {
        add_meta_box('aico-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aico-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aico_meta_box', 'aico_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aico-results">';
        echo '<textarea id="aico-input" style="width:100%;height:80px;display:none;">' . esc_textarea($content) . '</textarea>';
        echo '<button id="aico-analyze" class="button button-primary">Analyze Content (Free)</button>';
        echo '<div id="aico-score"></div>';
        echo '<div id="aico-tips"></div>';
        echo '<p><em>Upgrade to <strong>Pro</strong> for AI rewriting & keyword suggestions!</em></p>';
        echo '</div>';
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aico_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis (word count, readability score simulation)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? max(0, min(100, 200 - ($word_count / $sentence_count))) : 0; // Flesch-like simulation
        $score = ($readability + min(100, $word_count / 10)) / 2; // Composite score

        $tips = array();
        if ($word_count < 300) $tips[] = 'Add more content for better SEO.';
        if ($readability < 60) $tips[] = 'Improve readability: shorter sentences.';
        if (substr_count(strtolower($content), 'keyword') === 0) $tips[] = 'Include primary keywords.';

        $response = array(
            'score' => round($score),
            'words' => $word_count,
            'readability' => round($readability),
            'tips' => $tips,
            'upsell' => 'Unlock AI Rewrite & Pro Features'
        );

        wp_send_json_success($response);
    }

    public function premium_nag() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>';
        printf(__('Upgrade to <strong>AI Content Optimizer Pro</strong> for AI-powered rewriting, keyword research, and more! %s', 'ai-content-optimizer'), '<a href="https://example.com/pricing" target="_blank">Get Pro Now</a>');
        echo '</p></div>';
    }
}

new AIContentOptimizer();

// Inline JS for demo (in production, use separate file)
function aico_admin_js() {
    if (isset($_GET['page']) || (isset($GLOBALS['pagenow']) && ($GLOBALS['pagenow'] === 'post.php' || $GLOBALS['pagenow'] === 'post-new.php'))) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aico-analyze').click(function() {
                var content = $('#content').val() || $('#aico-input').val();
                $.post(aico_ajax.ajaxurl, {
                    action: 'aico_analyze_content',
                    nonce: aico_ajax.nonce,
                    content: content
                }, function(resp) {
                    if (resp.success) {
                        var html = '<strong>Score: ' + resp.data.score + '/100</strong><br>';
                        html += 'Words: ' + resp.data.words + ' | Readability: ' + resp.data.readability + '%<br>';
                        html += '<strong>Tips:</strong><ul>';
                        $.each(resp.data.tips, function(i, tip) { html += '<li>' + tip + '</li>'; });
                        html += '</ul><p><a href="https://example.com/pricing" target="_blank">' + resp.data.upsell + '</a></p>';
                        $('#aico-results').html(html);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'aico_admin_js');
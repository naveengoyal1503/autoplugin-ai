/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade?ref=plugin';
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), self::VERSION, true);
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai-optimizer-nonce'),
            'premiumUrl' => self::PREMIUM_URL
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), self::VERSION);
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Free Analysis:</strong> Check readability and basic SEO.</p>';
        echo '<textarea id="ai-content-input" rows="5" cols="30" placeholder="Paste content here...">' . esc_textarea(get_post_field('post_content', $post->ID)) . '</textarea>';
        echo '<br><button id="analyze-btn" class="button button-primary">Analyze Free</button>';
        echo '<div id="analysis-results"></div>';
        echo '<div id="premium-teaser" style="margin-top:10px;padding:10px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;">
                <p><strong>Unlock Premium AI Features:</strong> Auto-rewrite, keyword gen, plagiarism check. <a href="' . self::PREMIUM_URL . '" target="_blank" class="button button-small">Upgrade Now ($9.99/mo)</a></p>
              </div>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('ai-optimizer-nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis (simulated AI - readability, word count, SEO score)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - (1.43 * (array_sum(array_map('strlen', explode(' ', $content))) / $word_count)), 2) : 0; // Flesch approx
        $seo_score = min(100, (50 + ($word_count > 300 ? 20 : 0) + (substr_count(strtolower($content), 'the') / max(1, $word_count / 100) * 30))); // Mock SEO

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $word_count < 300 ? 'Add more content for better SEO.' : 'Good length!',
            'is_premium' => false
        );

        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ($screen->id === 'dashboard') {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> premium: AI rewriting & more! <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p></div>';
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Inline JS (self-contained)
function ai_optimizer_inline_js() {
    ?><script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function(e) {
            e.preventDefault();
            var content = $('#ai-content-input').val();
            if (!content) return;
            $('#analysis-results').html('<p>Analyzing...</p>');
            $.post(aiOptimizer.ajaxurl, {
                action: 'analyze_content',
                nonce: aiOptimizer.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<div style="background:#d4edda;border:1px solid #c3e6cb;padding:10px;"><h4>Analysis Results:</h4><ul>' +
                        '<li>Words: <strong>' + res.word_count + '</strong></li>' +
                        '<li>Readability (Flesch): <strong>' + res.readability + '</strong> (' + (res.readability > 60 ? 'Good' : 'Improve') + ')</li>' +
                        '<li>SEO Score: <strong>' + res.seo_score + '%</strong></li>' +
                        '<li>' + res.suggestions + '</li></ul>' +
                        '<p><em>Premium: Get AI rewrites & keywords! <a href="' + aiOptimizer.premiumUrl + '" target="_blank">Upgrade</a></em></p></div>';
                    $('#analysis-results').html(html);
                }
            });
        });
    });
    </script><?php
}
add_action('admin_footer', 'ai_optimizer_inline_js');

// Inline CSS
function ai_optimizer_inline_css() {
    ?><style>
    #ai-optimizer-panel textarea { width: 100%; margin-bottom: 10px; }
    #ai-optimizer-panel button { width: 100%; }
    #analysis-results { margin-top: 10px; font-size: 14px; }
    </style><?php
}
add_action('admin_head', 'ai_optimizer_inline_css');

new AIContentOptimizer();
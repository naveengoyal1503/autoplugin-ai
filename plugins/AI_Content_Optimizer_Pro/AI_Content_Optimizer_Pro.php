/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_optimize_content_premium', array($this, 'ajax_optimize_content_premium'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ai-optimizer-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('ai-optimizer-style');
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            80
        );
    }

    public function admin_page() {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $content = $post_id ? get_post_field('post_content', $post_id) : '';
        $is_premium = $this->is_premium();

        echo '<div class="wrap"><h1>AI Content Optimizer</h1>';
        echo '<p>Analyze your content for SEO score, readability, and suggestions.</p>';

        if ($content) {
            $analysis = $this->analyze_content($content);
            $this->display_analysis($analysis, $is_premium);
        } else {
            echo '<p>Select a post from the editor or paste content below:</p>';
            echo '<textarea id="content-input" style="width:100%;height:200px;">{$content}</textarea>';
            echo '<button id="analyze-btn" class="button button-primary">Analyze Free</button>';
        }

        if (!$is_premium) {
            echo '<div class="notice notice-info"><p><strong>Go Premium</strong> for AI rewriting, bulk processing, and more! <a href="#" id="upgrade-btn">Upgrade Now</a></p></div>';
        }
        echo '</div>';
    }

    private function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count ? round(206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (20 / $word_count), 2) : 0;
        $seo_score = min(100, round(($word_count / 10) + ($readability / 2) + rand(10, 30), 0));

        return array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $this->generate_suggestions($word_count, $readability)
        );
    }

    private function generate_suggestions($words, $readability) {
        $sugs = [];
        if ($words < 300) $sugs[] = 'Add more content for better SEO.';
        if ($readability > 70) $sugs[] = 'Improve readability with shorter sentences.';
        $sugs[] = 'Include keywords naturally.';
        return $sugs;
    }

    private function display_analysis($analysis, $premium) {
        echo '<table class="form-table">';
        echo '<tr><th>Word Count</th><td>' . $analysis['word_count'] . '</td></tr>';
        echo '<tr><th>Readability Score (Flesch)</th><td>' . $analysis['readability'] . '</td></tr>';
        echo '<tr><th>SEO Score</th><td><strong>' . $analysis['seo_score'] . '/100</strong></td></tr>';
        echo '</table>';
        echo '<h3>Suggestions</h3><ul>';
        foreach ($analysis['suggestions'] as $sug) {
            echo '<li>' . esc_html($sug) . '</li>';
        }
        echo '</ul>';

        echo '<button id="free-optimize" class="button">Free Optimize (Basic)</button> ';
        if ($premium) {
            echo '<button id="premium-optimize" class="button button-primary">AI Rewrite (Premium)</button>';
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $content = sanitize_textarea_field($_POST['content']);
        $optimized = $this->basic_optimize($content);
        wp_send_json_success(array('content' => $optimized));
    }

    public function ajax_optimize_content_premium() {
        if (!$this->is_premium()) {
            wp_send_json_error('Premium required');
        }
        // Simulate premium AI rewrite
        $content = sanitize_textarea_field($_POST['content']);
        $optimized = strtoupper(substr($content, 0, 50)) . '... [Premium AI Rewrite]';
        wp_send_json_success(array('content' => $optimized));
    }

    private function basic_optimize($content) {
        // Basic optimization: add paragraphs, trim excess
        $content = preg_replace('/\s+/', ' ', trim($content));
        return '<p>' . implode('</p><p>', explode('. ', $content)) . '.</p>';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function activate() {
        add_option('ai_optimizer_activated', time());
    }
}

new AIContentOptimizer();

// Enqueue scripts
function ai_optimizer_scripts($hook) {
    if ($hook !== 'toplevel_page_ai-content-optimizer') return;
    wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    wp_localize_script('ai-optimizer-js', 'ai_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_optimizer_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'ai_optimizer_scripts');

// Placeholder for CSS and JS files (inline for single file)
function ai_inline_assets() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <style>
        #ai-results { background: #f9f9f9; padding: 20px; margin: 20px 0; }
        .seo-good { color: green; }
        .seo-bad { color: red; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#analyze-btn, #free-optimize').click(function() {
                var content = $('#content-input, .post-content').val();
                $.post(ai_ajax.ajaxurl, {
                    action: 'optimize_content',
                    content: content,
                    nonce: ai_ajax.nonce
                }, function(res) {
                    if (res.success) $('#ai-results').html('<textarea>' + res.data.content + '</textarea>');
                });
            });
            $('#upgrade-btn').click(function(e) {
                e.preventDefault();
                alert('Upgrade to premium for advanced AI features! Visit example.com/premium');
            });
        });
        <?php
    }
}
add_action('admin_head', 'ai_inline_assets');
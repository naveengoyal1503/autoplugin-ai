/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for better SEO and readability. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'post.php' && $hook != 'post-new.php') return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-style', plugin_dir_url(__FILE__) . 'aco.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer (Lite)', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"><p><strong>Free Features:</strong> Readability score & basic keyword tips.</p>';
        echo '<textarea id="aco-content" rows="5" cols="30" placeholder="Content will be analyzed here..."></textarea>';
        echo '<br><button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-output"></div>';
        echo '<p><em>Upgrade to Premium for AI rewriting & bulk tools: <a href="https://example.com/premium" target="_blank">Get Premium</a></em></p></div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        if (empty($content)) {
            wp_send_json_error('No content provided.');
        }

        // Basic readability score (Flesch-Kincaid approximation)
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($content));
        $syllables = $this->count_syllables(strip_tags($content));
        $readability = 206.835 - 1.015 * ($words / max(1, $sentence_count)) - 84.6 * ($syllables / $words);

        // Basic keyword extraction (top 3 words)
        $words_array = preg_split('/\s+/', strtolower(strip_tags($content)));
        $word_freq = array_count_values(array_filter($words_array, function($w) { return strlen($w) > 3; }));
        arsort($word_freq);
        $top_keywords = array_slice(array_keys($word_freq), 0, 3);

        $output = '<h4>Readability Score: ' . round($readability, 1) . '/100</h4>';
        $output .= $readability > 60 ? '<p class="good">Good readability!</p>' : '<p class="poor">Improve sentence length & words.</p>';
        $output .= '<h4>Top Keywords:</h4><ul>';
        foreach ($top_keywords as $kw) {
            $output .= '<li>' . esc_html($kw) . '</li>';
        }
        $output .= '</ul><p><strong>Premium:</strong> AI rewrite, SEO scores, bulk optimize.</p>';

        wp_send_json_success($output);
    }

    private function count_syllables($text) {
        $syllables = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $word = strtolower($word);
            $syl = preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/ed/', $word) - preg_match_all('/es/', $word);
            $syllables += $syl > 0 ? $syl : 1;
        }
        return $syllables;
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for advanced features! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizerLite();

// Enqueue dummy JS/CSS (inline for single file)
function aco_inline_assets() {
    if (!wp_script_is('aco-script', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            var content = $('#aco-content').val() || $('#content').val();
            if (!content) return alert('Add some content first!');
            $('#aco-output').html('<p>Analyzing...</p>');
            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(res) {
                if (res.success) {
                    $('#aco-output').html(res.data);
                } else {
                    $('#aco-output').html('<p>Error: ' + res.data + '</p>');
                }
            });
        });
        $('#aco-content').on('input', function() {
            $('#aco-content').val($(this).val());
        });
    });
    </script>
    <style>
    #aco-results { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
    #aco-results h4 { margin: 10px 0 5px 0; font-size: 14px; }
    .good { color: green; }
    .poor { color: orange; }
    #aco-output ul { margin: 5px 0; padding-left: 20px; }
    </style>
    <?php
}
add_action('admin_footer-post.php', 'aco_inline_assets');
add_action('admin_footer-post-new.php', 'aco_inline_assets');
?>
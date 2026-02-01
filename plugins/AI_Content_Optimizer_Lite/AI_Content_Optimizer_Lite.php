/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'handle_optimize'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'premium_url' => 'https://example.com/premium-upgrade'
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer Lite', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content, 5);
        echo '<div id="aco-results">';
        echo '<p><strong>Word Count:</strong> ' . $word_count . '</p>';
        echo '<p><strong>Readability Score:</strong> ' . number_format($readability, 2) . ' (Flesch Reading Ease)</p>';
        echo '<p><strong>Top Keywords:</strong></p><ul>';
        foreach ($keywords as $kw) {
            echo '<li>' . esc_html($kw) . '</li>';
        }
        echo '</ul>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze & Optimize (Free)</button>';
        echo '<p><em>Upgrade to Premium for AI-powered suggestions, bulk processing, and more!</em> <a href="' . esc_url('https://example.com/premium-upgrade') . '" target="_blank" class="button">Go Premium</a></p>';
        echo '</div>';
    }

    public function handle_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $content = sanitize_textarea_field($_POST['content']);
        $suggestions = $this->generate_free_suggestions($content);
        wp_send_json_success($suggestions);
    }

    private function calculate_flesch_reading_ease($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count($text);
        $syllables = $this->count_syllables($text);
        if ($sentence_count == 0 || $words == 0) return 0;
        $asl = $words / $sentence_count;
        $asw = $syllables / $words;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]/', $text, $matches);
        return count($matches);
    }

    private function extract_keywords($text, $limit) {
        $text = strip_tags(strtolower($text));
        preg_match_all('/\b\w+\b/', $text, $words);
        $word_counts = array_count_values($words);
        arsort($word_counts);
        return array_slice(array_keys($word_counts), 0, $limit);
    }

    private function generate_free_suggestions($content) {
        $word_count = str_word_count(strip_tags($content));
        $suggestions = array();
        if ($word_count < 300) {
            $suggestions[] = 'Add more content: Aim for 500+ words for better SEO.';
        }
        $readability = $this->calculate_flesch_reading_ease($content);
        if ($readability < 60) {
            $suggestions[] = 'Improve readability: Use shorter sentences and simpler words.';
        }
        return $suggestions;
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for advanced AI suggestions and bulk optimization. <a href="https://example.com/premium-upgrade" target="_blank">Upgrade now</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function aco_add_inline_script() {
    if (isset($_GET['post']) || isset($_GET['page'])) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function() {
                var content = $('#content').val() || tinyMCE.activeEditor.getContent();
                $('#aco-results').append('<p>Analyzing...</p>');
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_optimize_content',
                    nonce: aco_ajax.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        var html = '<h4>Free Suggestions:</h4><ul>';
                        $.each(response.data, function(i, sug) {
                            html += '<li>' + sug + '</li>';
                        });
                        html += '</ul><p><a href="' + aco_ajax.premium_url + '" target="_blank">Get Premium for AI-powered optimizations!</a></p>';
                        $('#aco-results').html(html);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'aco_add_inline_script');
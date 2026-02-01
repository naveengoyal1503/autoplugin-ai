/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
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
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('wp_ajax_aco_upgrade', array($this, 'show_upgrade_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'post.php' && $hook != 'post-new.php') return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => false
        ));
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results">Click Analyze to optimize your content!</div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-upgrade" style="display:none;"><p><strong>Upgrade to Premium</strong> for AI rewriting & advanced SEO keywords! <a href="https://example.com/premium" target="_blank">Get Premium</a></p></div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_readability($content);
        $seo_score = min(100, ($word_count / 5) + ($readability * 20));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'seo_score' => round($seo_score),
            'suggestions' => $this->get_free_suggestions($content)
        );

        if (!get_option('aco_premium_key')) {
            $results['upgrade'] = true;
        }

        wp_send_json_success($results);
    }

    private function calculate_readability($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($content);
        $sentences = count($sentences);
        if ($sentences == 0) return 0;
        $syl = $this->estimate_syllables($content);
        $asl = $words / $sentences;
        return 206.835 - 1.015 * $asl - 84.6 * ($syl / $words);
    }

    private function estimate_syllables($text) {
        $text = strtolower($text);
        preg_match_all('/[aeiouy]+/', $text, $matches);
        return count($matches);
    }

    private function get_free_suggestions($content) {
        $suggestions = array();
        if (str_word_count($content) < 300) {
            $suggestions[] = 'Add more content for better SEO (aim for 1000+ words).';
        }
        if (strpos($content, 'href=') === false) {
            $suggestions[] = 'Include internal/external links.';
        }
        return $suggestions;
    }

    public function show_upgrade_nag() {
        echo '<div class="notice notice-info"><p>Unlock AI rewriting with Premium! <a href="https://example.com/premium">Upgrade Now</a></p></div>';
    }

    public function activate() {
        add_option('aco_premium_key', false);
    }
}

new AIContentOptimizer();

// Dummy JS file content (in real plugin, separate file)
/*
function aco_js() {
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            var content = $('#content').val();
            $.post(aco_ajax.ajax_url, {
                action: 'aco_analyze_content',
                nonce: aco_ajax.nonce,
                content: content
            }, function(response) {
                if (response.success) {
                    var res = response.data;
                    var html = '<p><strong>Words:</strong> ' + res.word_count + '</p>' +
                               '<p><strong>Readability (Flesch):</strong> ' + res.readability + '</p>' +
                               '<p><strong>SEO Score:</strong> ' + res.seo_score + '/100</p>';
                    if (res.suggestions.length) {
                        html += '<ul>';
                        res.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                        html += '</ul>';
                    }
                    if (res.upgrade) {
                        $('#aco-upgrade').show();
                    }
                    $('#aco-results').html(html);
                }
            });
        });
    });
}
*/
?>
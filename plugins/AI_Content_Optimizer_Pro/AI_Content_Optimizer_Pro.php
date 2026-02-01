/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress content for better readability, SEO, and engagement. Freemium model with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade?ref=plugin';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco_optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_save', 'aco_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        echo '<div id="aco-results">';
        if ($score) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '%</p>';
        }
        echo '<p><button id="aco-analyze" class="button button-primary">Analyze Content</button></p>';
        echo '<p><a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank" class="button button-secondary">Upgrade to Pro (AI Rewrite & SEO)</a></p>';
        echo '</div>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic analysis: Flesch Reading Ease approximation
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($content));
        $syllables = $this->count_syllables(strip_tags($content));

        if ($sentence_count == 0 || $words == 0) {
            wp_die(json_encode(array('score' => 0, 'tips' => array('No content to analyze.'))));
        }

        $asl = $words / $sentence_count;
        $asw = $syllables / $words;
        $flesch = 206.835 - (1.015 * $asl) - (84.6 * $asw);
        $score = max(0, min(100, round($flesch)));

        // Tips
        $tips = array();
        if ($asl > 25) $tips[] = 'Shorten sentences for better readability.';
        if ($asw > 1.8) $tips[] = 'Use simpler words.';
        if (substr_count(strip_tags($content), '<h2') < 3) $tips[] = 'Add more subheadings.';
        $tips[] = '<strong>Pro Tip:</strong> Upgrade for AI-powered keyword optimization and auto-rewriting!';

        update_post_meta($post_id, '_aco_score', $score);
        wp_die(json_encode(array('score' => $score, 'tips' => $tips)));
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) < 3) continue;
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]$/', $word) + 1;
        }
        return $syllables;
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_save')) return;
        // Meta saved via AJAX
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Dummy JS file content (in real plugin, save as aco.js)
/*
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: $('#post_ID').val()
        }, function(response) {
            var data = JSON.parse(response);
            var html = '<p><strong>Score: ' + data.score + '%</strong></p><ul>';
            $.each(data.tips, function(i, tip) {
                html += '<li>' + tip + '</li>';
            });
            html += '</ul>';
            $('#aco-results').html(html);
        });
    });
});
*/
?>
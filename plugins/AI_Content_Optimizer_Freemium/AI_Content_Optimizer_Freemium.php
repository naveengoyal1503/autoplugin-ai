/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-analysis-result">Click Analyze for basic SEO & readability score (Premium: AI Rewrite & Keywords)</div>';
        echo '<button id="aco-analyze-btn" class="button">Analyze Content</button>';
        echo '<p><a href="' . self::PREMIUM_URL . '" target="_blank" class="button button-primary">Upgrade to Premium</a></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, ($word_count / 10) + ($readability / 2)); // Simple heuristic

        $result = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'seo_score' => round($seo_score),
            'message' => $seo_score > 70 ? 'Good!' : 'Needs improvement. Premium AI can auto-optimize!',
            'is_premium' => false
        );

        wp_send_json_success($result);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text) ?: 1;
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        preg_match_all('/[aeiouy]+/', $text, $matches);
        return count($matches);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ($screen->id === 'dashboard') {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong>: Auto-rewrite, keyword gen & more! <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Dummy JS file reference - in real plugin, include aco.js with AJAX handler
// aco.js content would be: jQuery(document).ready(function($){ $('#aco-analyze-btn').click(function(){ $.post(aco_ajax.ajax_url, {action:'aco_analyze_content', post_id: $('#post_ID').val(), nonce: aco_ajax.nonce}, function(r){ if(r.success) $('#aco-analysis-result').html('Words: '+r.data.word_count+' | Readability: '+r.data.readability+' | SEO: '+r.data.seo_score+'<br>'+r.data.message); }); }); });
?>
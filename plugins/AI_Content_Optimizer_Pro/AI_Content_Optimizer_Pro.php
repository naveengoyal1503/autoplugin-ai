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
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-script', plugin_dir_url(__FILE__) . 'aico.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aico-script', 'aico_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aico_nonce')));
        wp_enqueue_style('aico-style', plugin_dir_url(__FILE__) . 'aico.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aico-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aico_meta_box', 'aico_meta_box_nonce');
        echo '<div id="aico-results"></div>';
        echo '<button id="aico-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small><strong>Pro:</strong> Unlock AI rewriting & keywords for $9/mo. <a href="https://example.com/pricing" target="_blank">Upgrade Now</a></small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aico_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->flesch_readability($content);
        $seo_score = min(100, (50 + ($word_count > 500 ? 20 : 0) + ($readability > 60 ? 30 : 0)));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'seo_score' => round($seo_score),
            'is_premium' => false,
            'message' => 'Free analysis complete. Upgrade for AI-powered optimizations!'
        );

        wp_send_json_success($results);
    }

    private function flesch_readability($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count($text);
        $syllables = $this->count_syllables($text);
        if ($sentence_count == 0 || $words == 0) return 0;
        $asl = $words / $sentence_count;
        $asw = $syllables / $words;
        return 206.835 - 1.015 * $asl - 84.6 * $asw;
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/e$/', $word);
        }
        return max(1, $syllables);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> AI features: Rewrite content, keyword research & more. <a href="https://example.com/pricing" target="_blank">Get Pro</a></p></div>';
    }

    public function activate() {
        add_option('aico_activated_time', time());
    }
}

new AIContentOptimizer();

// Inline JS and CSS for single file
$js = "<script>jQuery(document).ready(function($){ $('#aico-analyze').click(function(){ var post_id = $('#post_ID').val(); $.post(aico_ajax.ajax_url, {action: 'aico_analyze_content', post_id: post_id, nonce: aico_ajax.nonce}, function(resp){ if(resp.success){ $('#aico-results').html('<p><strong>Words:</strong> ' + resp.data.word_count + ' | <strong>Readability:</strong> ' + resp.data.readability + '% | <strong>SEO Score:</strong> ' + resp.data.seo_score + '%</p><p>' + resp.data.message + '</p>'); } }); }); });</script>";
$css = '<style>#aico-results { margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; } #aico-analyze { width: 100%; margin-top: 10px; }</style>';
add_action('admin_head-post.php', function() { echo $js . $css; });
add_action('admin_head-post-new.php', function() { echo $js . $css; });

?>
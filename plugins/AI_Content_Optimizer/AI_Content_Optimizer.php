/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free basic features; premium for advanced AI tools.
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
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_notices', array($this, 'premium_nag'));
        }
        $this->is_premium = get_option('aco_premium_active', false);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-analysis-result">Click "Analyze" to scan your content.</div>';
        echo '<button id="aco-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-loader" style="display:none;">Analyzing...</div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ? round($word_count / $sentence_count, 1) : 0;

        $score = 80;
        if ($word_count < 300) $score -= 20;
        if ($avg_sentence_length > 25) $score -= 15;
        if ($readability < 60) $score -= 15;

        $result = array(
            'score' => min(100, max(0, $score)),
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'avg_sentence' => $avg_sentence_length,
            'tips' => $this->get_tips($word_count, $readability, $avg_sentence_length)
        );

        if (!$this->is_premium) {
            $result['premium_teaser'] = 'Upgrade to Premium for AI keyword suggestions and auto-rewriting!';
        }

        wp_send_json_success($result);
    }

    private function calculate_flesch_reading_ease($text) {
        $word_count = str_word_count($text);
        $sentence_count = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentence_count);
        $syllables = $this->count_syllables($text);

        if ($sentence_count == 0 || $word_count == 0) return 0;

        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = array('/tion/', '/sion/', '/ious/', '/[^aeiouy]+[aeiouy]+/', '/ed/');
        $replace = array('', '', '', '1', '');
        return preg_match_all('/[aeiouy]{2}/', $text) + preg_match_all($rules, $text, $matches) + strlen(preg_replace($rules, $replace, $text));
    }

    private function get_tips($words, $readability, $avg) {
        $tips = array();
        if ($words < 300) $tips[] = 'Add more content: Aim for 500+ words.';
        if ($avg > 25) $tips[] = 'Shorten sentences: Keep under 20 words.';
        if ($readability < 60) $tips[] = 'Improve readability: Use simpler words.';
        return $tips;
    }

    public function premium_nag() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for AI rewriting and keywords! <a href="https://example.com/premium" target="_blank">Upgrade now ($4.99/mo)</a></p></div>';
        }
    }

    public function activate() {
        update_option('aco_premium_active', false);
    }
}

AIContentOptimizer::get_instance();

// Premium stub - simulate license check
function aco_premium_features() {
    // In real premium, check license via API
    return false;
}
?>
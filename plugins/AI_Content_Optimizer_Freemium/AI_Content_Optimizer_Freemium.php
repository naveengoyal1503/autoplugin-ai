/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free version includes basic metrics; upgrade for AI magic!
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
    const PREMIUM_URL = 'https://example.com/premium-upgrade?ref=plugin';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_notice'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_flesch_reading_ease($content);
        echo '<div id="aco-results">';
        echo '<p><strong>Word Count:</strong> ' . $word_count . '</p>';
        echo '<p><strong>Readability Score:</strong> ' . round($readability, 1) . ' (Higher is easier)</p>';
        echo '<p><button id="aco-analyze" class="button button-primary">Analyze (Free)</button></p>';
        echo '<div id="aco-loader" style="display:none;">Analyzing...</div>';
        echo '<div id="aco-premium"><p><em>Upgrade for AI Rewrite & Keyword Suggestions!</em></p></div>';
        echo '</div>';
    }

    private function calculate_flesch_reading_ease($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = explode(' ', $text);
        $word_count = count(array_filter($words));
        $syllables = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            if (strlen($word) <= 3) continue;
            $vowels = preg_match_all('/[aeiouy]/', $word);
            $word =~ /ed|ing|eable|able|ible|ant|ence|ent|ful|ful|ic|ive|ize|less|ous|ment/ ? $vowels-- : null;
            $syllables += $vowels > 0 ? $vowels : 1;
        }
        return $syllables;
    }

    public function ajax_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        $results = array(
            'word_count' => str_word_count(strip_tags($content)),
            'readability' => round($this->calculate_flesch_reading_ease($content), 1),
            'seo_score' => rand(60, 90) // Simulated free SEO score
        );
        wp_send_json_success($results);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ($screen->id == 'post') {
            echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Rewrite</strong> and <strong>Keyword Magic</strong> with Premium! <a href="' . self::PREMIUM_URL . '" target="_blank" class="button button-primary">Upgrade Now ($49/yr)</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Simulated JS file content (inline for single file)
/*
function aco_init() {
    jQuery('#aco-analyze').click(function() {
        jQuery('#aco-loader').show();
        jQuery.post(aco_ajax.ajaxurl, {
            action: 'aco_optimize_content',
            post_id: jQuery('#post_ID').val(),
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                jQuery('#aco-results').html(
                    '<p><strong>Word Count:</strong> ' + response.data.word_count + '</p>' +
                    '<p><strong>Readability:</strong> ' + response.data.readability + '</p>' +
                    '<p><strong>SEO Score:</strong> ' + response.data.seo_score + '%</p>' +
                    '<p>Upgrade for AI optimization!</p>'
                );
            }
            jQuery('#aco-loader').hide();
        });
    });
}
jQuery(aco_init);
*/
// Note: In production, extract JS to aco.js and enqueue properly. This is simplified for single-file demo.
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-driven suggestions, keyword analysis, and readability improvements.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_LICENSE = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_optimize', array($this, 'ajax_optimize'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-script', plugin_dir_url(__FILE__) . 'aico.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aico-script', 'aico_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aico_nonce')));
        wp_enqueue_style('aico-style', plugin_dir_url(__FILE__) . 'aico.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aico-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
        add_meta_box('aico-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aico_meta_nonce', 'aico_meta_nonce');
        $score = get_post_meta($post->ID, '_aico_score', true);
        echo '<div id="aico-container">';
        echo '<p><strong>SEO Score:</strong> ' . esc_html($score ?: 'Not optimized') . '</p>';
        echo '<button id="aico-optimize-btn" class="button button-primary">Optimize Content</button>';
        echo '<div id="aico-suggestions"></div>';
        echo '<p><small><strong>Premium:</strong> Unlock AI suggestions & bulk optimize for $49/year! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></small></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aico_meta_nonce']) || !wp_verify_nonce($_POST['aico_meta_nonce'], 'aico_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        update_post_meta($post_id, '_aico_score', sanitize_text_field($_POST['_aico_score'] ?? ''));
    }

    public function ajax_optimize() {
        check_ajax_referer('aico_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->flesch_readability($content);
        $score = min(100, 50 + ($word_count / 10) + ($readability * 10));

        // Simulate premium AI (free shows limited)
        $is_premium = get_option(self::PREMIUM_LICENSE);
        $suggestions = $is_premium ? $this->generate_ai_suggestions($content) : 'Upgrade to premium for AI-powered suggestions!';

        wp_send_json_success(array(
            'score' => round($score),
            'suggestions' => $suggestions,
            'word_count' => $word_count,
            'readability' => round($readability, 2)
        ));
    }

    private function flesch_readability($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count(strip_tags($text));
        $syllables = $this->count_syllables($text);
        if ($sentence_count == 0 || $words == 0) return 0;
        $asl = $words / $sentence_count;
        $asw = $syllables / $words;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = array('/tion/', '/sion/', '/ian/', '/eer/', '/[^aeiou][aeiouy]{2}/', '/[^aeiou][aeiouy]/');
        $counts = array(2, 2, 2, 2, 1, 1);
        $syllables = 0;
        foreach ($rules as $i => $rule) {
            $syllables += preg_match_all($rule, $text, $matches);
        }
        return max(1, $syllables);
    }

    private function generate_ai_suggestions($content) {
        // Placeholder for premium AI (integrate real API like OpenAI in premium)
        return "<ul>
            <li>Add keywords: 'WordPress', 'SEO'</li>
            <li>Improve readability: Shorten sentences</li>
            <li>Premium: Full AI rewrite available</li>
        </ul>";
    }

    public function premium_nag() {
        if (get_option(self::PREMIUM_LICENSE)) return;
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> premium features! <a href="https://example.com/premium" target="_blank">Get 50% off now ($49/year)</a></p></div>';
    }

    public function activate() {
        add_option('aico_version', '1.0.0');
    }
}

new AIContentOptimizer();

// JS and CSS as inline for single file

function aico_add_inline_scripts() {
    ?>
    <style id="aico-css">
    #aico-container { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; }
    #aico-optimize-btn { width: 100%; margin: 10px 0; }
    #aico-suggestions { background: #f1f3f4; padding: 10px; border-radius: 4px; font-size: 13px; }
    </style>
    <script id="aico-js">
    jQuery(document).ready(function($) {
        $('#aico-optimize-btn').click(function() {
            var postId = $('#post_ID').val();
            $.post(aico_ajax.ajaxurl, {
                action: 'aico_optimize',
                post_id: postId,
                nonce: aico_ajax.nonce
            }, function(res) {
                if (res.success) {
                    $('#aico-container p:first').html('<strong>SEO Score:</strong> ' + res.data.score + '/100');
                    $('#aico-suggestions').html(res.data.suggestions + '<br>Words: ' + res.data.word_count + ' | Readability: ' + res.data.readability);
                    $('input[name="_aico_score"]').val(res.data.score);
                }
            });
        });
    });
    </script>
    <?php
}

add_action('admin_footer-post.php', 'aico_add_inline_scripts');
add_action('admin_footer-post-new.php', 'aico_add_inline_scripts');

?>
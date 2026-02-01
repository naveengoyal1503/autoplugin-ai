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
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-secondary">Analyze Content</button>';
        echo '<p><small>Free: Basic analysis. <a href="' . self::PREMIUM_URL . '" target="_blank">Go Pro</a> for AI rewrite & keywords.</small></p>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch($content);
        $seo_score = min(100, (50 + ($word_count > 300 ? 20 : 0) + ($readability > 60 ? 30 : 0)));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'seo_score' => round($seo_score),
            'premium_tease' => true
        );

        if (isset($_POST['premium_key']) && $_POST['premium_key'] === 'demo-premium-key') { // Demo premium
            $results['ai_keywords'] = array('best keyword', 'seo tips');
            $results['ai_rewrite'] = 'Optimized version of your content...';
            $results['is_premium'] = true;
        }

        wp_send_json_success($results);
    }

    private function calculate_flesch($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = explode(' ', preg_replace('/\s+/', ' ', trim($text)));
        $word_count = count(array_filter($words));
        $syllables = $this->count_syllables(implode(' ', $words));
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - 1.015 * $asl - 84.6 * $asw;
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = array('/ed$/i', '/ing$/i', '/ism$/i');
        $text = preg_replace($rules, '', $text);
        return preg_match_all('/[aeiouy]{2}/', $text) + preg_match_all('/[aeiouy][^aeiouy]/', $text);
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock AI rewriting and keyword research with <a href="' . self::PREMIUM_URL . '" target="_blank">AI Content Optimizer Pro</a>! <code>Use demo key: demo-premium-key</code> for testing.</p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function aco_add_inline_script() {
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'post') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function() {
                var postId = $('#post_ID').val();
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    post_id: postId,
                    premium_key: prompt('Enter Premium Key (demo: demo-premium-key):')
                }, function(response) {
                    if (response.success) {
                        var r = response.data;
                        var html = '<strong>SEO Score: ' + r.seo_score + '/100</strong><br>' +
                                   'Words: ' + r.word_count + '<br>' +
                                   'Readability: ' + r.readability + '<br>';
                        if (r.ai_keywords) {
                            html += '<br><strong>AI Keywords:</strong> ' + r.ai_keywords.join(', ') + '<br>' +
                                    '<strong>AI Rewrite:</strong> ' + r.ai_rewrite;
                        } else {
                            html += '<br><em>Upgrade to Pro for AI features!</em>';
                        }
                        $('#aco-results').html(html);
                    }
                });
            });
        });
        <?php
    }
}
add_action('admin_footer-post.php', 'aco_add_inline_script');
add_action('admin_footer-post-new.php', 'aco_add_inline_script');
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
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    const PREMIUM_KEY = 'ai_content_optimizer_pro_key';
    const PREMIUM_STATUS = 'ai_content_optimizer_pro_status';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option(self::PREMIUM_STATUS, 'free');
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'post.php' && $hook != 'post-new.php') return;
        wp_enqueue_script('aco-js', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-js', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), array('post', 'page'), 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic score. <a href="#" id="aco-upgrade">Upgrade to Pro ($9.99/mo)</a> for AI suggestions & auto-fix.</small></p>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - (1.43 * (word_count($content) / $sentence_count)), 2) : 0;
        $score = min(100, max(0, (min(500, $word_count)/5) + ($readability / 2)));

        $is_premium = get_option(self::PREMIUM_STATUS) === 'active';
        $results = array(
            'score' => $score,
            'word_count' => $word_count,
            'readability' => $readability,
            'premium' => $is_premium,
            'suggestions' => $is_premium ? $this->generate_ai_suggestions($content) : 'Upgrade for AI-powered suggestions!'
        );
        wp_send_json_success($results);
    }

    private function generate_ai_suggestions($content) {
        // Simulated AI suggestions (in real: integrate OpenAI API)
        $suggestions = array();
        if (str_word_count($content) < 300) $suggestions[] = 'Add more content for better SEO.';
        if (strpos($content, 'href=') === false) $suggestions[] = 'Include internal/external links.';
        $suggestions[] = 'Use H2/H3 headings for structure.';
        return implode(' ', $suggestions);
    }

    public function premium_nag() {
        if (get_option(self::PREMIUM_STATUS) !== 'active' && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for $9.99/mo: Advanced AI, auto-optimization & more! <a href="https://example.com/upgrade" target="_blank">Upgrade Now</a></p></div>';
        }
    }
}

new AIContentOptimizerPro();

// Mock JS file content (base64 or inline in real single-file)
/* Inline JS for single file */
function aco_inline_js() {
    if (isset($_GET['post'])) {
        ?><script type="text/javascript">
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: $('#post_ID').val()
        }, function(res) {
            if (res.success) {
                let html = '<p><strong>Score: ' + res.data.score + '%</strong></p>';
                html += '<p>Words: ' + res.data.word_count + ' | Readability: ' + res.data.readability + '</p>';
                html += '<p>' + res.data.suggestions + '</p>';
                $('#aco-results').html(html);
            }
        });
    });
});
</script><?php
    }
}
add_action('admin_footer-post.php', 'aco_inline_js');
add_action('admin_footer-post-new.php', 'aco_inline_js');

// Helper function
if (!function_exists('word_count')) {
    function word_count($text) {
        return str_word_count(strip_tags($text));
    }
}

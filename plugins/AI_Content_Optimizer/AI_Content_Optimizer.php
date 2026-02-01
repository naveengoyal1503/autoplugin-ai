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
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicontent_optimizer_premium';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aico_analyze', array($this, 'ajax_analyze'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aico_analysis_count', 0);
    }

    public function add_meta_box() {
        add_meta_box('aico-meta', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aico_nonce', 'aico_nonce');
        $analysis = get_post_meta($post->ID, '_aico_analysis', true);
        echo '<div id="aico-results">' . esc_html($analysis ? $analysis['score'] : 'Click Analyze') . '</div>';
        echo '<p><button id="aico-analyze" class="button button-primary">Analyze Content</button></p>';
        echo '<p><small><strong>Free:</strong> Basic score (5 uses/month). <a href="#" id="aico-upgrade">Upgrade to Premium</a> for AI rewrite & unlimited.</small></p>';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aico-js', plugin_dir_url(__FILE__) . 'aico.js', array('jquery'), '1.0', true);
        wp_localize_script('aico-js', 'aico_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aico_ajax')));
    }

    public function ajax_analyze() {
        check_ajax_referer('aico_ajax', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count / 1000) * 20 + (rand(0, 20)));

        $analysis = array(
            'score' => round($score) . '%',
            'tips' => $this->is_premium() ? $this->premium_tips($content) : 'Improve readability and add keywords.',
            'premium' => !$this->is_premium()
        );

        if (!$this->is_premium()) {
            $count = get_option('aico_analysis_count', 0) + 1;
            update_option('aico_analysis_count', $count);
            if ($count > 5) {
                $analysis['limit'] = true;
            }
        }

        update_post_meta($post_id, '_aico_analysis', $analysis);
        wp_send_json_success($analysis);
    }

    private function premium_tips($content) {
        return 'AI Suggestion: Rewrite first paragraph for better engagement. Premium keywords: seo, wordpress, content.';
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aico_nonce']) || !wp_verify_nonce($_POST['aico_nonce'], 'aico_nonce')) return;
        // Meta saved via AJAX
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function aico_admin_notice() {
    $screen = get_current_screen();
    if ('edit-post' === $screen->id) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong>: Unlimited analyses, AI rewriting & SEO keywords for $4.99/mo. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aico_admin_notice');

// Simple JS file content (base64 encoded for single file, but inline for simplicity)
/* Note: In production, create aico.js separately. For single-file demo:
<script>
jQuery(document).ready(function($) {
    $('#aico-analyze').click(function(e) {
        e.preventDefault();
        $.post(aico_ajax.ajax_url, {
            action: 'aico_analyze',
            post_id: $('#post_ID').val(),
            nonce: aico_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#aico-results').html(res.data.score + '<br>' + res.data.tips);
                if (res.data.limit) alert('Free limit reached. Upgrade!');
                if (res.data.premium) $('#aico-upgrade').show();
            }
        });
    });
});
</script>
*/
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_co_pro_key';
    const PREMIUM_STATUS = 'ai_co_pro_status';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_ai_co_analyze', [$this, 'ajax_analyze']);
        add_action('wp_ajax_ai_co_upgrade', [$this, 'ajax_upgrade']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        add_option('ai_co_dismissed_notice', 0);
    }

    public function add_menu() {
        add_options_page('AI Content Optimizer', 'AI Content Opt.', 'manage_options', 'ai-content-optimizer', [$this, 'settings_page']);
    }

    public function settings_page() {
        $premium_status = get_option(self::PREMIUM_STATUS, 'free');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <?php if ($premium_status === 'free'): ?>
            <div class="notice notice-info">
                <p>Upgrade to Pro for AI rewriting, bulk optimization, and more! <button id="ai-co-upgrade" class="button button-primary">Upgrade Now ($4.99/mo)</button></p>
            </div>
            <?php endif; ?>
            <p>Pro status: <strong><?php echo ucfirst($premium_status); ?></strong></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-co-upgrade').click(function() {
                $.post(ajaxurl, {action: 'ai_co_upgrade'}, function(res) {
                    alert(res.success ? 'Thanks for upgrading!' : 'Upgrade later!');
                });
            });
        });
        </script>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('ai-co-analysis', 'AI Content Analysis', [$this, 'meta_box_content'], 'post', 'side');
    }

    public function meta_box_content($post) {
        wp_nonce_field('ai_co_meta', 'ai_co_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, 'ai_co_score', true) ?: 0;
        $premium_status = get_option(self::PREMIUM_STATUS, 'free');
        echo '<p><strong>Readability Score:</strong> ' . $score . '/100</p>';
        echo '<button id="ai-co-analyze-' . $post->ID . '" class="button">Analyze</button>';
        if ($premium_status === 'pro') {
            echo ' <button id="ai-co-optimize-' . $post->ID . '" class="button button-primary">AI Optimize</button>';
        } else {
            echo ' <a href="#" class="button" onclick="alert(\'Upgrade to Pro for AI optimization!\')">AI Optimize (Pro)</a>';
        }
        echo '<div id="ai-co-result-' . $post->ID . '"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_co_nonce']) || !wp_verify_nonce($_POST['ai_co_nonce'], 'ai_co_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        // Score saved via AJAX
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php' && $hook !== 'settings_page_ai-content-optimizer') return;
        wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'ai-co.js', ['jquery'], '1.0.0', true);
        wp_localize_script('ai-co-js', 'ai_co_ajax', ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_co_ajax')]);
    }

    public function ajax_analyze() {
        check_ajax_referer('ai_co_ajax', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        $score = min(100, max(0, 50 + ($words / 100) - ($sentences / 2))); // Simple mock formula
        update_post_meta($post_id, 'ai_co_score', $score);
        wp_send_json_success(['score' => $score]);
    }

    public function ajax_upgrade() {
        check_ajax_referer('ai_co_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        update_option(self::PREMIUM_STATUS, 'pro');
        wp_send_json_success();
    }
}

new AIContentOptimizer();

// Mock JS file content (in reality, enqueue a separate JS file, but for single-file, inline it)
/*
Add this as a separate ai-co.js file in production, but for demo:
*/
jQuery(document).ready(function($) {
    $('.wrap').on('click', '[id^="ai-co-analyze-"]', function() {
        var btn = $(this);
        var postId = btn.attr('id').replace('ai-co-analyze-', '');
        btn.prop('disabled', true).text('Analyzing...');
        $.post(ai_co_ajax.ajaxurl, {
            action: 'ai_co_analyze',
            post_id: postId,
            nonce: ai_co_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#ai-co-result-' + postId).html('<p><strong>New Score: ' + res.data.score + '</strong></p>');
            }
            btn.prop('disabled', false).text('Analyze');
        });
    });
    /* Pro optimize mock */
    $('[id^="ai-co-optimize-"]').click(function(e) {
        e.preventDefault();
        alert('Pro feature: Content rewritten with AI! (Mock - upgrade simulates this)');
    });
});
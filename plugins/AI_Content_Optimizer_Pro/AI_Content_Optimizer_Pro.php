/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for SEO, readability, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_optimization_score'));
        add_shortcode('ai_optimize_score', array($this, 'display_score_shortcode'));
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <p>Analyze your content with AI for SEO, readability, and engagement scores.</p>
            <form method="post" action="options.php">
                <?php settings_fields('ai_optimizer_options'); ?>
                <?php do_settings_sections('ai_optimizer'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Analysis</th>
                        <td><input type="checkbox" name="ai_optimizer_auto" value="1" <?php checked(get_option('ai_optimizer_auto', 0)); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('ai-optimizer-box', 'AI Optimization Score', array($this, 'meta_box_content'), 'post', 'side', 'high');
        add_meta_box('ai-optimizer-box', 'AI Optimization Score', array($this, 'meta_box_content'), 'page', 'side', 'high');
    }

    public function meta_box_content($post) {
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $seo_score = get_post_meta($post->ID, '_ai_seo_score', true);
        $readability_score = get_post_meta($post->ID, '_ai_readability_score', true);
        echo '<p><strong>Overall Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '%</p>';
        echo '<p><strong>SEO:</strong> ' . esc_html($seo_score ?: 'N/A') . '</p>';
        echo '<p><strong>Readability:</strong> ' . esc_html($readability_score ?: 'N/A') . '</p>';
        echo '<p><a href="#" id="analyze-now" data-postid="' . $post->ID . '" class="button">Analyze Now</a></p>';
    }

    public function handle_optimize() {
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simple AI-like analysis (rule-based for demo; integrate real AI API in pro)
        $word_count = str_word_count(strip_tags($content));
        $seo_score = min(100, ($word_count / 10) + (substr_count(strtolower($content), 'keyword') * 10));
        $readability_score = min(100, 80 - abs(200 - $word_count) / 2);
        $overall = round(($seo_score + $readability_score) / 2);

        update_post_meta($post_id, '_ai_optimizer_score', $overall);
        update_post_meta($post_id, '_ai_seo_score', $seo_score);
        update_post_meta($post_id, '_ai_readability_score', $readability_score);

        wp_send_json_success(array('score' => $overall, 'seo' => $seo_score, 'readability' => $readability_score));
    }

    public function save_optimization_score($post_id) {
        if (get_option('ai_optimizer_auto', 0)) {
            $this->handle_optimize_ajax($post_id);
        }
    }

    private function handle_optimize_ajax($post_id) {
        // Trigger analysis on save if auto enabled (simplified)
        $content = get_post_field('post_content', $post_id);
        // Same logic as handle_optimize
        $word_count = str_word_count(strip_tags($content));
        $seo_score = min(100, ($word_count / 10));
        $readability_score = min(100, 80 - abs(200 - $word_count) / 2);
        $overall = round(($seo_score + $readability_score) / 2);
        update_post_meta($post_id, '_ai_optimizer_score', $overall);
        update_post_meta($post_id, '_ai_seo_score', $seo_score);
        update_post_meta($post_id, '_ai_readability_score', $readability_score);
    }

    public function display_score_shortcode($atts) {
        $post_id = get_the_ID();
        $score = get_post_meta($post_id, '_ai_optimizer_score', true);
        if (!$score) return '';
        return '<div style="background:#4CAF50;color:white;padding:10px;border-radius:5px;text-align:center;"><strong>AI Score: ' . $score . '%</strong></div>';
    }
}

// Enqueue admin scripts
function ai_optimizer_admin_scripts($hook) {
    if ($hook != 'post.php' && $hook != 'post-new.php' && $hook != 'settings_page_ai-optimizer') return;
    wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
    wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
}
add_action('admin_enqueue_scripts', 'ai_optimizer_admin_scripts');

// Create JS file placeholder (in real plugin, include separate JS file)
function ai_optimizer_js_inline() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-now').on('click', function(e) {
            e.preventDefault();
            var postId = $(this).data('postid');
            $.post(ajaxurl, {
                action: 'optimize_content',
                post_id: postId,
                _ajax_nonce: ai_ajax.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'ai_optimizer_js_inline');

// Register settings
add_action('admin_init', function() {
    register_setting('ai_optimizer_options', 'ai_optimizer_auto');
});

new AIContentOptimizer();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('ai_optimizer_pro_activated')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for real AI integration, A/B testing, and more! <a href="https://example.com/pro">Upgrade now</a></p></div>';
    }
});
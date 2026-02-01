/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO with AI-powered insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    private $is_premium = false;

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        // Premium nag
        add_action('admin_notices', array($this, 'premium_nag'));
        $this->is_premium = get_option('ai_optimizer_premium') === 'yes';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>SEO Score:</strong> <span id="ai-score">' . esc_html($score ?: 'Not analyzed') . '</span></p>';
        echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="ai-results"></div>';
        echo '<p><small>Upgrade to Pro for AI rewriting & bulk tools: <a href="' . self::PREMIUM_URL . '" target="_blank">Get Pro</a></small></p>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (free version: basic keyword density, readability)
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count > 500 ? 20 : 0) + (substr_count(strtolower($content), 'keyword') > 3 ? 30 : 0));

        if (!$this->is_premium && isset($_POST['rewrite']) && $_POST['rewrite'] === '1') {
            wp_die(json_encode(array('error' => 'Rewrite available in Pro')));
        }

        update_post_meta($post_id, '_ai_optimizer_score', $score);
        $tips = $this->generate_tips($content, $score);

        wp_die(json_encode(array(
            'score' => $score,
            'tips' => $tips,
            'premium_only' => !$this->is_premium && rand(0,1) // Nag randomly
        )));
    }

    private function generate_tips($content, $score) {
        $tips = array(
            'Add more headings (H2/H3).',
            'Include target keywords naturally.',
            'Aim for 500+ words.'
        );
        if (!$this->is_premium) {
            $tips[] = 'Pro: Get AI-generated rewrites!';
        }
        return $tips;
    }

    public function premium_nag() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for rewriting, bulk optimization & more! <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade Now</a></p></div>';
        }
    }
}

new AIContentOptimizer();

// Dummy JS file reference - in real plugin, include optimizer.js
// Content of optimizer.js:
/*
jQuery(document).ready(function($) {
    $('#analyze-btn').click(function() {
        $.post(ai_optimizer.ajax_url, {
            action: 'optimize_content',
            nonce: ai_optimizer.nonce,
            post_id: $('#post_ID').val()
        }, function(response) {
            var data = JSON.parse(response);
            $('#ai-score').text(data.score + '%');
            var tips = data.tips.map(function(tip) { return '<li>' + tip + '</li>'; }).join('');
            $('#ai-results').html('<ul>' + tips + '</ul>');
            if (data.premium_only) {
                $('#ai-results').append('<p><a href="https://example.com/premium-upgrade" target="_blank">Upgrade to Pro</a></p>');
            }
        });
    });
});
*/
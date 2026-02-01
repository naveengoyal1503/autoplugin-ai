/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';
    const PREMIUM_URL = 'https://example.com/premium-upgrade'; // Replace with your sales page

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aco_analyze', [$this, 'ajax_analyze']);
        add_filter('plugin_row_meta', [$this, 'add_premium_link'], 10, 2);
    }

    public function add_menu() {
        add_posts_page('AI Content Optimizer', 'AI Optimizer', 'edit_posts', 'ai-optimizer', [$this, 'settings_page']);
    }

    public function settings_page() {
        $is_premium = $this->is_premium();
        echo '<div class="wrap"><h1>AI Content Optimizer</h1>';
        if (!$is_premium) {
            echo '<div class="notice notice-warning"><p>Upgrade to Pro for unlimited analyses, AI rewrites, and more! <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Get Pro Now</a></p></div>';
        }
        echo '<p>Basic analysis available on posts. Premium users get advanced features.</p>';
        echo '</div>';
    }

    public function add_meta_box() {
        add_meta_box('ai-optimizer', 'AI Content Optimizer', [$this, 'meta_box_content'], 'post', 'side');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $analysis = get_post_meta($post->ID, '_aco_analysis', true);
        $is_premium = $this->is_premium();
        echo '<p><strong>SEO Score:</strong> ' . ($analysis ? esc_html($analysis['score']) : 'Not analyzed') . '%</p>';
        echo '<p><button id="aco-analyze" class="button button-primary">Analyze Content</button></p>';
        if (!$is_premium) {
            echo '<p class="description">Limited to 5 free analyses per day. <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Upgrade for unlimited</a>.</p>';
        }
        echo '<div id="aco-results"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_ajax_nonce'),
            'is_premium' => $this->is_premium(),
            'limit' => $this->get_daily_limit()
        ]);
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!$this->is_premium() && $this->get_daily_count() >= 5) {
            wp_send_json_error('Daily limit reached. Upgrade to Pro!');
        }

        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count / 1000) * 20 + (strpos($content, 'keyword') !== false ? 15 : 0) + rand(0, 15)); // Mock AI analysis
        $tips = $this->generate_tips($content, $score);

        $analysis = ['score' => $score, 'tips' => $tips, 'word_count' => $word_count];
        update_post_meta($post_id, '_aco_analysis', $analysis);

        if (!$this->is_premium()) {
            $this->increment_daily_count();
        }

        wp_send_json_success($analysis);
    }

    private function generate_tips($content, $score) {
        $tips = [];
        if ($score < 70) $tips[] = 'Add more keywords and improve readability.';
        if (strlen($content) < 1000) $tips[] = 'Increase content length for better SEO.';
        $tips[] = 'Use short paragraphs and headings.';
        if ($this->is_premium()) {
            $tips[] = 'Premium: AI-powered rewrite available!';
        }
        return $tips;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated'; // Mock; integrate real license check
    }

    private function get_daily_count() {
        $today = date('Y-m-d');
        $counts = get_option('aco_daily_counts', []);
        return intval($counts[$today] ?? 0);
    }

    private function increment_daily_count() {
        $today = date('Y-m-d');
        $counts = get_option('aco_daily_counts', []);
        $counts[$today] = ($counts[$today] ?? 0) + 1;
        update_option('aco_daily_counts', $counts);
    }

    private function get_daily_limit() {
        return $this->is_premium() ? 999 : 5;
    }

    public function add_premium_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
            $links[] = '<a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Get Pro</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Mock JS file content (in real, save as aco.js)
/*
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Analyzing...');
        $.post(aco_ajax.ajaxurl, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: $('#post_ID').val()
        }, function(res) {
            if (res.success) {
                var html = '<p><strong>Score: ' + res.data.score + '%</strong></p><ul>';
                $.each(res.data.tips, function(i, tip) {
                    html += '<li>' + tip + '</li>';
                });
                html += '</ul>';
                $('#aco-results').html(html);
            } else {
                alert(res.data);
            }
            $btn.prop('disabled', false).text('Analyze Content');
        });
    });
});
*/
?>
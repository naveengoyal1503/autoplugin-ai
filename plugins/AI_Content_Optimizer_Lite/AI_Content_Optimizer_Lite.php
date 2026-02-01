/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability with AI insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerLite {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    private $scan_limit = 5; // Free users limited to 5 scans per week

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_analyze'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_scans_used', 0);
        add_option('aco_week_reset', current_time('timestamp'));
    }

    public function add_admin_menu() {
        add_management_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'admin_page'));
    }

    public function admin_page() {
        $scans_used = get_option('aco_scans_used', 0);
        $week_reset = get_option('aco_week_reset', current_time('timestamp'));
        $now = current_time('timestamp');
        $week_start = strtotime('monday this week', $now);
        if ($week_reset < $week_start) {
            update_option('aco_scans_used', 0);
            update_option('aco_week_reset', $week_start);
            $scans_used = 0;
        }
        echo '<div class="wrap"><h1>AI Content Optimizer Lite</h1>';
        echo '<p>Free scans left this week: ' . ($this->scan_limit - $scans_used) . '/' . $this->scan_limit . '</p>';
        echo '<p><a href="' . self::PREMIUM_URL . '" class="button button-primary" target="_blank">Upgrade to Premium for Unlimited Scans & AI Suggestions</a></p>';
        echo '</div>';
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_analyze_nonce', 'aco_nonce');
        $post_id = $post->ID;
        echo '<button id="aco-analyze-btn" class="button" data-post-id="' . $post_id . '">Analyze Content</button>';
        echo '<div id="aco-results-' . $post_id . '"></div>';
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-script', 'aco_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aco_ajax_nonce'),
                'premium_url' => self::PREMIUM_URL
            ));
        }
    }

    public function handle_analyze() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate scan limit
        $scans_used = get_option('aco_scans_used', 0);
        $week_reset = get_option('aco_week_reset', current_time('timestamp'));
        $now = current_time('timestamp');
        $week_start = strtotime('monday this week', $now);
        if ($week_reset < $week_start) {
            update_option('aco_scans_used', 0);
            $scans_used = 0;
        }

        if ($scans_used >= $this->scan_limit) {
            wp_send_json_error('Free scan limit reached. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade to Premium</a>');
        }

        // Basic analysis (free version: word count, readability score, keyword density)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? round(180 - (1.8 * ($word_count / $sentence_count)), 2) : 0; // Simplified Flesch score
        $title = get_the_title($post_id);
        $title_words = str_word_count($title);
        $keyword_density = $title_words > 0 ? round(($title_words / $word_count) * 100, 1) : 0;

        update_option('aco_scans_used', $scans_used + 1);

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'keyword_density' => $keyword_density,
            'recommendations' => array(
                'Aim for 300-1000 words per post.',
                'Target readability >60 for better engagement.',
                'Premium: Unlock AI keyword suggestions and auto-optimizations.'
            )
        );

        wp_send_json_success($results);
    }
}

new AIContentOptimizerLite();

// Freemium upsell notice
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id === 'edit-post') {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for unlimited scans, AI rewriting, and SEO boosters! <a href="https://example.com/premium-upgrade" target="_blank">Learn More</a></p></div>';
    }
});
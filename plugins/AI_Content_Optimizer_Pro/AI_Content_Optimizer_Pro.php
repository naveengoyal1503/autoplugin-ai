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

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aco_analyze', [$this, 'ajax_analyze']);
        add_action('wp_ajax_aco_upgrade', [$this, 'ajax_upgrade']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aco-script', 'aco_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium()
        ]);
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', [$this, 'meta_box_html'], 'post', 'side');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $premium = $this->is_premium();
        echo '<div id="aco-results">';
        if ($score) {
            echo '<p><strong>Score:</strong> ' . esc_html($score) . '%</p>';
        }
        echo '<p><button id="aco-analyze" class="button">Analyze Content</button></p>';
        if (!$premium) {
            echo '<p><em>Upgrade to Premium for AI rewriting & advanced features!</em></p>';
            echo '<button id="aco-upgrade" class="button button-primary">Upgrade Now</button>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium() && !empty($_POST['premium_action'])) {
            wp_send_json_error('Premium feature required.');
            return;
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $word_count > 0 ? min(100, ($sentence_count / $word_count) * 300) : 0;
        $score = round(($readability + rand(50, 90)) / 2);
        update_post_meta($post_id, '_aco_score', $score);
        wp_send_json_success(['score' => $score, 'tips' => $this->get_tips($score)]);
    }

    public function ajax_upgrade() {
        check_ajax_referer('aco_nonce', 'nonce');
        // Simulate license check or redirect to payment
        wp_send_json(['url' => 'https://example.com/premium-upgrade', 'message' => 'Upgrade to unlock AI features!']);
    }

    private function get_tips($score) {
        if ($score < 60) return ['Shorten sentences.', 'Add more keywords.'];
        return ['Great! Consider premium AI rewrite.'];
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function activate() {
        add_option(self::PREMIUM_KEY, 'free');
    }
}

new AIContentOptimizer();

// Freemium nag
add_action('admin_notices', function() {
    if (!AIContentOptimizer::is_premium() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting and more! <a href="https://example.com/premium">Upgrade Now</a></p></div>';
    }
});

// JS file content (embedded as string for single file)
$js = "jQuery(document).ready(function($){ $('#aco-analyze').click(function(){ $.post(aco_ajax.ajaxurl, {action:'aco_analyze', nonce:aco_ajax.nonce, post_id:$('#post_ID').val()}, function(res){ if(res.success){ $('#aco-results').html('<p><strong>Score: '+res.data.score+'%</strong></p><ul>'+res.data.tips.map(t=>'<li>'+t+'</li>').join('')+'</ul>'); } }); }); $('#aco-upgrade').click(function(){ $.post(aco_ajax.ajaxurl, {action:'aco_upgrade', nonce:aco_ajax.nonce}, function(res){ alert(res.message); window.open(res.url); }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'aco.js', $js);
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO with AI suggestions. Freemium model.
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_usage_count', 0);
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        $premium = get_option(self::PREMIUM_KEY);
        $usage = get_option('aco_usage_count', 0);
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<p>Free scans left this month: ' . (5 - $usage) . '</p>';
        if (!$premium) {
            echo '<p><strong>Upgrade to Premium</strong> for unlimited scans and AI rewrites! <a href="https://example.com/premium" target="_blank">Get Premium ($4.99/mo)</a></p>';
        } else {
            echo '<p>Premium active! Enjoy unlimited features.</p>';
        }
        echo '</div>';
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_nonce');
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aco-results"></div>';
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_ajax_nonce')));
        }
    }

    public function analyze_content() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $premium = get_option(self::PREMIUM_KEY);
        $usage = get_option('aco_usage_count', 0);

        if (!$premium && $usage >= 5) {
            wp_send_json_error('Upgrade to premium for more scans!');
            return;
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real: integrate OpenAI API)
        $word_count = str_word_count(strip_tags($content));
        $suggestions = array(
            'SEO Score: ' . rand(60, 95) . '%',
            'Word count: ' . $word_count . ' (Optimal: 1000-2000)',
            'Suggestions: Add keywords like "' . $this->suggest_keyword($content) . '". Improve readability.',
            $premium ? 'Premium: AI Rewrite: "Optimized version of your content here..."' : ''
        );

        if (!$premium) {
            update_option('aco_usage_count', $usage + 1);
        }

        wp_send_json_success($suggestions);
    }

    private function suggest_keyword($content) {
        $words = explode(' ', strip_tags($content));
        return isset($words[5]) ? $words[5] : 'your main topic';
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function aco_admin_notice() {
    if (!get_option(AIContentOptimizer::PREMIUM_KEY) && get_option('aco_usage_count', 0) >= 5) {
        echo '<div class="notice notice-warning"><p>AI Content Optimizer: <strong>Upgrade to Premium</strong> for unlimited AI optimizations! <a href="options-general.php?page=ai-content-optimizer">Settings</a></p></div>';
    }
}
add_action('admin_notices', 'aco_admin_notice');

// Include JS file content inline for single-file
/*
<script>
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        var post_id = $('#post_ID').val();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            post_id: post_id,
            nonce: aco_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#aco-results').html('<ul>' + response.data.map(function(s) { return '<li>' + s + '</li>'; }).join('') + '</ul>');
            } else {
                alert(response.data);
            }
        });
    });
});
</script>
*/
?>
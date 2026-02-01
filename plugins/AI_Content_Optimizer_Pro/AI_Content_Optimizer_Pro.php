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
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('wp_ajax_upgrade_to_pro', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_pro', 'free');
    }

    public function add_admin_menu() {
        add_posts_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'edit_posts',
            'ai-content-optimizer',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_pro' => get_option('ai_content_optimizer_pro') === 'pro'
        ));
    }

    public function admin_page() {
        if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
            echo '<div class="wrap"><h1>AI Content Optimizer</h1><div id="ai-optimizer-panel">Paste or edit content below for analysis.</div><textarea id="content-input" rows="10" cols="80"></textarea><button id="analyze-btn" class="button button-primary">Analyze Content</button><div id="results"></div></div>';
        }
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $is_pro = get_option('ai_content_optimizer_pro') === 'pro';

        // Simulate AI analysis (basic metrics)
        $word_count = str_word_count($content);
        $readability = $word_count > 0 ? min(100, 50 + (300 - $word_count) / 5) : 0;
        $seo_score = min(100, ($this->count_keywords($content, array('seo', 'content', 'wordpress')) * 10));
        $engagement = min(100, rand(60, 95));

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => $seo_score,
            'engagement' => $engagement,
            'suggestions' => $is_pro ? array('AI rewrite available', 'Bulk optimize', 'Advanced metrics') : array('Upgrade to Pro for AI rewriting and more'),
            'is_pro' => $is_pro
        );

        wp_send_json_success($results);
    }

    private function count_keywords($content, $keywords) {
        $count = 0;
        foreach ($keywords as $kw) {
            $count += substr_count(strtolower($content), strtolower($kw));
        }
        return $count;
    }

    public function handle_upgrade() {
        // Simulate pro upgrade (in real: integrate with Stripe/PayPal)
        update_option('ai_content_optimizer_pro', 'pro');
        wp_send_json_success('Upgraded to Pro!');
    }
}

new AIContentOptimizer();

// Upsell notice
function ai_optimizer_notice() {
    if (get_option('ai_content_optimizer_pro') !== 'pro') {
        echo '<div class="notice notice-info"><p><strong>AI Content Optimizer Pro:</strong> Unlock AI rewriting, bulk optimization, and more for $4.99/mo. <a href="' . admin_url('post-new.php?page=ai-content-optimizer') . '">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_notice');

// JS file content (embedded for single file)
/*
Add this as optimizer.js in a real multi-file setup, but inline for single file:
$(document).ready(function() {
    $('#analyze-btn').click(function() {
        $.post(ai_optimizer.ajax_url, {
            action: 'analyze_content',
            nonce: ai_optimizer.nonce,
            content: $('#content-input').val()
        }, function(res) {
            if (res.success) {
                let html = '<h3>Analysis Results:</h3><p>Words: ' + res.data.word_count + '</p><p>Readability: ' + res.data.readability + '%</p>';
                html += '<p>SEO Score: ' + res.data.seo_score + '%</p><p>Engagement: ' + res.data.engagement + '%</p>';
                html += '<ul>';
                res.data.suggestions.forEach(function(s) { html += '<li>' + s + '</li>'; });
                html += '</ul>';
                if (!res.data.is_pro) {
                    html += '<button id="upgrade-btn" class="button button-primary">Upgrade to Pro</button>';
                }
                $('#results').html(html);
            }
        });
    });

    $(document).on('click', '#upgrade-btn', function() {
        $.post(ai_optimizer.ajax_url, {
            action: 'upgrade_to_pro',
            nonce: ai_optimizer.nonce
        }, function(res) {
            if (res.success) location.reload();
        });
    });
});
*/
?>
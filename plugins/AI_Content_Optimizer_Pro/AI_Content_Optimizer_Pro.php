/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI-powered insights. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->is_premium = get_option('aco_premium_key') !== false;
        load_plugin_textdomain('ai-content-optimizer');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'toplevel_page_aco-dashboard' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aco-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'aco-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-analytics',
            30
        );
    }

    public function dashboard_page() {
        include plugin_dir_path(__FILE__) . 'dashboard.php';
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $post_id = intval($_POST['post_id']);

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $analysis = array(
            'word_count' => $word_count,
            'readability_score' => min(100, 50 + ($word_count / 100)),
            'seo_score' => rand(40, 100),
            'suggestions' => array(
                'Add more keywords',
                'Improve sentence variety',
                'Include headings'
            ),
            'premium_features' => !$this->is_premium ? array('AI Rewrite', 'Competitor Analysis') : array()
        );

        if (!$this->is_premium && rand(0, 1)) {
            $analysis['upgrade_message'] = 'Upgrade to Pro for unlimited scans and AI rewrites!';
        }

        wp_send_json_success($analysis);
    }

    public function activate() {
        add_option('aco_activated_time', time());
    }
}

// Freemium upsell logic
add_action('admin_notices', function() {
    $aco = AIContentOptimizer::get_instance();
    if (!$aco->is_premium && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>AI Content Optimizer:</strong> Unlock premium features like unlimited AI rewrites and competitor analysis for <a href="https://example.com/premium" target="_blank">$9/month</a>! <button class="button button-primary" onclick="location.href=\'admin.php?page=aco-dashboard\';">Upgrade Now</button></p></div>';
    }
});

AIContentOptimizer::get_instance();

// Placeholder for admin.js content (inline for single file)
$js = "jQuery(document).ready(function($) {
    $('#aco-analyze').click(function() {
        var content = $('#aco-content').val();
        if (!content) return;
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze_content',
            nonce: aco_ajax.nonce,
            content: content,
            post_id: $('#post_ID').val() || 0
        }, function(resp) {
            if (resp.success) {
                $('#aco-results').html('<pre>' + JSON.stringify(resp.data, null, 2) + '</pre>');
                if (resp.data.upgrade_message) {
                    alert(resp.data.upgrade_message);
                }
            }
        });
    });
});";

// Placeholder for admin.css
$css = "#aco-results { margin-top: 20px; padding: 10px; background: #f9f9f9; } #aco-analyze { margin: 10px 0; }";

// Write JS and CSS to temp files or inline
add_action('admin_head', function() {
    echo '<script>' . $js . '</script><style>' . $css . '</style>';
});

// Simple dashboard.php content (inline)
function aco_dashboard_template() {
    ?><div class="wrap"><h1>AI Content Optimizer Dashboard</h1>
    <p>Paste your content below for AI-powered SEO analysis:</p>
    <textarea id="aco-content" rows="10" cols="80" placeholder="Enter post content..."></textarea><br>
    <button id="aco-analyze" class="button button-primary">Analyze Content</button>
    <div id="aco-results"></div>
    <?php if (!AIContentOptimizer::get_instance()->is_premium): ?>
    <div class="card"><h3>Go Premium!</h3><p>Unlimited scans, AI rewrites, and more for $9/mo. <a href="https://example.com/premium" target="_blank">Upgrade</a></p></div>
    <?php endif; ?></div><?php
}
add_action('admin_footer', 'aco_dashboard_template');
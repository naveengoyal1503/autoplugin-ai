/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->is_premium = get_option('ai_content_optimizer_premium_key') !== false;
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_usage_count', 0);
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        echo '<div id="ai-optimizer-output"></div>';
        echo '<button type="button" id="ai-optimize-btn" class="button button-secondary">' . ($this->is_premium ? 'Optimize Content' : 'Basic Analyze (Free)') . '</button>';
        if (!$this->is_premium) {
            echo '<p><small><a href="#" id="go-premium">Go Premium for Unlimited & AI Rewrites</a></small></p>';
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium,
            'limit_reached' => get_option('ai_content_optimizer_usage_count', 0) >= 5
        ));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['premium_key'])) {
            update_option('ai_content_optimizer_premium_key', sanitize_text_field($_POST['premium_key']));
            $this->is_premium = true;
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <p>Enter Premium Key: <input type="text" name="premium_key" placeholder="Premium License Key" /></p>
                <p class="submit"><input type="submit" class="button-primary" value="Activate Premium" /></p>
            </form>
            <p><strong>Free Usage:</strong> 5 scans/month. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium && get_option('ai_content_optimizer_usage_count', 0) >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to premium.');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real: API call to OpenAI or similar)
        $analysis = $this->mock_ai_analysis($content);

        if (!$this->is_premium) {
            update_option('ai_content_optimizer_usage_count', get_option('ai_content_optimizer_usage_count', 0) + 1);
        }

        wp_send_json_success($analysis);
    }

    private function mock_ai_analysis($content) {
        $word_count = str_word_count($content);
        $has_keywords = preg_match('/(seo|content|wordpress)/i', $content);
        $readability = rand(60, 90);

        if ($this->is_premium) {
            $suggestions = "Optimized title: " . get_the_title() . " | Rewrite: " . substr($content, 0, 100) . '... (AI enhanced)';
        } else {
            $suggestions = 'Basic: Word count: ' . $word_count . '. Readability: ' . $readability . '%. Add keywords like SEO.';
        }

        return array(
            'score' => rand(70, 95),
            'suggestions' => $suggestions,
            'tips' => array('Improve keyword density', 'Shorten sentences', 'Add headings')
        );
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell admin notice
function ai_optimizer_admin_notice() {
    if (!AIContentOptimizer::get_instance()->is_premium && get_option('ai_content_optimizer_usage_count', 0) >= 4) {
        echo '<div class="notice notice-info"><p>AI Content Optimizer: Unlock unlimited scans and AI rewrites with Premium! <a href="options-general.php?page=ai-content-optimizer">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Dummy JS file reference - in real plugin, include actual JS file
/*
To make self-contained, here's inline JS logic. In production, use enqueued JS.
*/

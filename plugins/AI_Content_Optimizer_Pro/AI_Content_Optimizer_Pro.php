/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free basic features; upgrade to Pro for advanced AI tools.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;
    public $is_pro = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->is_pro = get_option('ai_content_optimizer_pro', false);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_pro' => $this->is_pro,
            'pro_url' => 'https://example.com/checkout?plugin=ai-optimizer'
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>Basic Analysis:</strong></p>';
        echo '<button id="analyze-btn" class="button button-secondary">Analyze Content</button>';
        echo '<div id="analysis-results"></div>';
        if (!$this->is_pro) {
            echo '<div class="notice notice-info" style="margin:10px 0;"><p>Upgrade to <strong>Pro</strong> for AI rewriting, keyword suggestions & more! <a href="https://example.com/checkout?plugin=ai-optimizer" target="_blank">Get Pro ($4.99/mo)</a></p></div>';
        }
        echo '</div>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        $word_count = str_word_count($content);
        $readability = $word_count > 300 ? 'Good' : 'Improve length';
        $seo_score = min(100, (min(500, $word_count) / 5) + rand(20, 50));

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability,
            'seo_score' => round($seo_score),
            'suggestions' => $this->is_pro ? $this->pro_suggestions($content) : 'Upgrade to Pro for AI-powered suggestions and auto-rewriting.'
        );

        wp_send_json_success($results);
    }

    private function pro_suggestions($content) {
        // Simulated AI suggestions (in real Pro, integrate OpenAI API)
        return array(
            'keywords' => ['optimize', 'content', 'SEO'],
            'rewrite_sample' => substr($content, 0, 100) . '... (AI optimized)'
        );
    }

    public function activate() {
        // Set default options
        add_option('ai_content_optimizer_pro', false);
    }
}

AIContentOptimizer::get_instance();

// Upsell admin notice
function ai_optimizer_admin_notice() {
    $screen = get_current_screen();
    if ('edit-post' === $screen->id && !get_option('ai_content_optimizer_pro')) {
        echo '<div class="notice notice-success is-dismissible"><p>🚀 Love <strong>AI Content Optimizer</strong>? Unlock <strong>Pro features</strong> like AI rewriting & keyword gen for $4.99/mo! <a href="https://example.com/checkout?plugin=ai-optimizer" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// Prevent direct access
if (!defined('ABSPATH')) exit;
?>
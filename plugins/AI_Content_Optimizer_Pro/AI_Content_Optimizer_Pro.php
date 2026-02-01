/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upsell.
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
    private $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_premium = get_option('ai_content_optimizer_premium_key') !== false;

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_nag'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function activate() {
        add_option('ai_content_optimizer_activated', time());
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);

        echo '<div id="ai-optimizer-results">';
        if ($score) {
            echo '<p><strong>SEO Score:</strong> ' . esc_html($score) . '%</p>';
            echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
        } else {
            echo '<p>Click "Analyze Now" to scan your content.</p>';
        }
        echo '<button type="button" id="ai-analyze-btn" class="button button-primary">Analyze Now (Free)</button>';
        if (!$this->is_premium) {
            echo '<br><br><a href="#" id="ai-upgrade-btn" class="button button-secondary">Upgrade to Pro for AI Rewrite</a>';
        }
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium ? '1' : '0'
        ));
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'ai-optimizer.css', array(), '1.0.0');
    }

    public function premium_nag() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> for AI rewriting and advanced SEO tools! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
        }
    }

    public function add_settings_link($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        return $links;
    }
}

// AJAX Handlers
add_action('wp_ajax_ai_analyze_content', 'ai_optimizer_analyze_content');
function ai_optimizer_analyze_content() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $content = sanitize_textarea_field($_POST['content']);

    // Simulate analysis (free version: basic rules-based)
    $word_count = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content);
    $avg_sentence = $word_count / max(1, count($sentences));
    $score = 50;
    if ($word_count > 500) $score += 20;
    if ($avg_sentence > 15 && $avg_sentence < 25) $score += 15;
    if (preg_match_all('/<h[1-6]>/', $content) > 2) $score += 10;
    $score = min(100, $score);

    $suggestions = array();
    if ($word_count < 300) $suggestions[] = 'Add more content for better engagement.';
    if ($avg_sentence > 25) $suggestions[] = 'Shorten sentences for readability.';

    update_post_meta($post_id, '_ai_optimizer_score', $score);
    update_post_meta($post_id, '_ai_optimizer_suggestions', implode('; ', $suggestions));

    wp_send_json_success(array('score' => $score, 'suggestions' => implode('<br>', $suggestions)));
}

add_action('wp_ajax_ai_upgrade', 'ai_optimizer_upgrade');
function ai_optimizer_upgrade() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    // Simulate premium check (in real: validate license key via API)
    if (isset($_POST['license_key'])) {
        update_option('ai_content_optimizer_premium_key', sanitize_text_field($_POST['license_key']));
        wp_send_json_success('Activated!');
    } else {
        wp_send_json_error('Invalid key');
    }
}

AIContentOptimizer::get_instance();

// Note: Create empty ai-optimizer.js and ai-optimizer.css in plugin dir for full functionality.
// JS example: AJAX call to analyze on button click.
// Real premium would integrate OpenAI API or similar (key required in pro).
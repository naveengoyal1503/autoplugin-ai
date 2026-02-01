/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerPro {
    private static $instance = null;
    private $premium_key = '';

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
        $this->premium_key = get_option('ai_cop_pro_key', '');
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_ai_cop_analyze', array($this, 'ajax_analyze'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_nag'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('ai-cop-js', plugin_dir_url(__FILE__) . 'ai-cop.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-cop-js', 'ai_cop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_cop_nonce'),
            'is_premium' => $this->is_premium()
        ));
        wp_add_inline_script('ai-cop-js', '/* Dummy AI simulation for demo */ function mockAIResponse(content) { return { score: Math.random()*100, suggestions: ["Add more keywords", "Improve readability"], premium: Math.random()>0.7 }; }');
    }

    public function add_meta_box() {
        add_meta_box('ai-cop-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-cop-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_cop_meta_box', 'ai_cop_meta_box_nonce');
        echo '<div id="ai-cop-container">';
        echo '<button id="ai-cop-analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="ai-cop-results"></div>';
        echo '<div id="ai-cop-premium-upsell" style="display:none;"><p><strong>Go Premium!</strong> Unlock AI rewriting for $4.99/mo. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
        echo '</div>';
    }

    public function ajax_analyze() {
        check_ajax_referer('ai_cop_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real version, call external AI API)
        $analysis = $this->mock_ai_analysis($content);

        if (!$this->is_premium() && $analysis['needs_premium']) {
            wp_send_json_success(array('premium_required' => true));
        }

        wp_send_json_success($analysis);
    }

    private function mock_ai_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $score = min(100, 50 + ($word_count / 10));
        $premium = rand(0, 10) > 7;
        return array(
            'score' => round($score, 1),
            'readability' => rand(60, 90),
            'seo_score' => rand(50, 95),
            'suggestions' => array(
                'Use more H2 headings',
                'Add internal links',
                'Optimize keyword density'
            ),
            'needs_premium' => $premium
        );
    }

    public function premium_nag() {
        if (!$this->is_premium() && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> full features! <a href="https://example.com/premium">Upgrade for $4.99/month</a></p></div>';
        }
    }

    private function is_premium() {
        return !empty($this->premium_key) && $this->validate_premium_key($this->premium_key);
    }

    private function validate_premium_key($key) {
        // Simulate validation (in real: API call to your server)
        return strlen($key) > 10 && substr($key, 0, 5) === 'PRO--';
    }

    public function activate() {
        add_option('ai_cop_version', '1.0.0');
    }

    public function deactivate() {}
}

AIContentOptimizerPro::get_instance();

// Settings page
add_action('admin_menu', function() {
    add_options_page('AI Content Optimizer Settings', 'AI COP Pro', 'manage_options', 'ai-cop-settings', 'ai_cop_settings_page');
});

function ai_cop_settings_page() {
    if (isset($_POST['premium_key'])) {
        update_option('ai_cop_pro_key', sanitize_text_field($_POST['premium_key']));
        echo '<div class="notice notice-success"><p>Key updated!</p></div>';
    }
    $key = get_option('ai_cop_pro_key', '');
    echo '<div class="wrap"><h1>AI Content Optimizer Pro Settings</h1><form method="post">';
    echo '<label>Premium License Key: <input type="text" name="premium_key" value="' . esc_attr($key) . '" style="width:300px;"></label><br><br>';
    echo '<p class="description">Get your key at <a href="https://example.com/premium" target="_blank">example.com/premium</a> for $4.99/month.</p>';
    submit_button();
    echo '</form></div>';
}

// JS file content (base64 or inline, but for single file, inline via wp_add_inline_script already handled)

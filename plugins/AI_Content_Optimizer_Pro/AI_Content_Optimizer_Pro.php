/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress content for SEO, readability, and engagement. Freemium model with premium AI features.
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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_premium = get_option('ai_content_optimizer_premium_key') !== false;
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = get_post_meta($post->ID, '_ai_analysis', true);

        echo '<div id="ai-optimizer-results">';
        if ($analysis) {
            echo '<p><strong>Previous Analysis:</strong><br>' . esc_html($analysis) . '</p>';
        }
        echo '<button type="button" id="ai-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="ai-loading" style="display:none;">Analyzing...</div>';
        echo '<div id="ai-results"></div>';
        if (!$this->is_premium) {
            echo '<p><a href="https://example.com/premium" target="_blank">Upgrade to Premium for AI Suggestions & Bulk Optimize</a></p>';
        }
        echo '</div>';
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
        }
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['premium_key'])) {
            update_option('ai_content_optimizer_premium_key', sanitize_text_field($_POST['premium_key']));
            $this->is_premium = true;
        }
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post"><table class="form-table">';
        echo '<tr><th>Premium License Key</th><td><input type="text" name="premium_key" value="" placeholder="Enter key for premium features" class="regular-text"><p class="description">Get premium at <a href="https://example.com/premium" target="_blank">example.com/premium</a></p></td></tr>';
        echo '</table><p><input type="submit" class="button-primary" value="Activate Premium"></p></form></div>';
    }

    public function save_post($post_id) {
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

    public function add_action_links($links) {
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Settings</a>';
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        return $links;
    }

    public function activate() {
        // Activation code
    }

    public function deactivate() {
        // Deactivation code
    }
}

// AJAX handler
add_action('wp_ajax_ai_analyze_content', 'ai_analyze_content_handler');
function ai_analyze_content_handler() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    // Basic free analysis
    $word_count = str_word_count(strip_tags($content));
    $readability = $word_count > 300 ? 'Good' : 'Improve length';
    $seo_score = min(100, ($word_count / 5));
    $analysis = "Words: $word_count | Readability: $readability | SEO Score: $seo_score/100";

    if (AIContentOptimizer::get_instance()->is_premium) {
        // Simulate premium AI (in real: call OpenAI API)
        $analysis .= " | AI Tip: Add more headings for better structure.";
    }

    update_post_meta($post_id, '_ai_analysis', $analysis);
    wp_send_json_success($analysis);
}

AIContentOptimizer::get_instance();

// JS file content (embedded for single file)
/*
Add this as optimizer.js but inline for single file:
$(document).ready(function($) {
    $('#ai-analyze').click(function() {
        var $btn = $(this);
        var $results = $('#ai-results');
        var $loading = $('#ai-loading');
        $btn.prop('disabled', true);
        $loading.show();
        $.post(ai_ajax.ajax_url, {
            action: 'ai_analyze_content',
            post_id: $('input[name="post_ID"]').val(),
            nonce: ai_ajax.nonce
        }, function(response) {
            $loading.hide();
            if (response.success) {
                $results.html('<p><strong>Analysis:</strong> ' + response.data + '</p>');
            }
            $btn.prop('disabled', false);
        });
    });
});
*/
?>
/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO using AI suggestions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;
    private $api_key = '';
    private $is_premium = false;
    private $usage_count = 0;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->is_premium = get_option('ai_content_optimizer_premium', false);
        $this->api_key = get_option('ai_content_optimizer_api_key', '');
        $this->usage_count = get_option('ai_content_optimizer_usage', 0);
    }

    public function activate() {
        add_option('ai_content_optimizer_usage', 0);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'admin_page'),
            'dashicons-editor-alignleft',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'toplevel_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'isPremium' => $this->is_premium,
            'usageLeft' => 5 - $this->usage_count,
            'upgradeUrl' => 'https://example.com/premium'
        ));
    }

    public function admin_page() {
        if (isset($_POST['api_key'])) {
            update_option('ai_content_optimizer_api_key', sanitize_text_field($_POST['api_key']));
            $this->api_key = sanitize_text_field($_POST['api_key']);
        }
        if (isset($_POST['activate_premium'])) {
            update_option('ai_content_optimizer_premium', true);
            $this->is_premium = true;
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->api_key) {
            wp_die(json_encode(array('error' => 'API key required')));
        }

        if (!$this->is_premium && $this->usage_count >= 5) {
            wp_die(json_encode(array('error' => 'Free limit reached. Upgrade to premium.')));
        }

        $content = sanitize_textarea_field($_POST['content']);
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => 'Optimize this content for SEO: improve readability, add keywords, structure with headings.'),
                    array('role' => 'user', 'content' => $content)
                ),
                'max_tokens' => 1000
            ))
        ));

        if (is_wp_error($response)) {
            wp_die(json_encode(array('error' => 'API request failed')));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $optimized = $body['choices']['message']['content'] ?? 'Optimization failed.';

        if (!$this->is_premium) {
            $this->usage_count++;
            update_option('ai_content_optimizer_usage', $this->usage_count);
        }

        wp_die(json_encode(array('success' => true, 'content' => $optimized)));
    }
}

AIContentOptimizer::get_instance();

// Create JS and CSS files placeholders (in real plugin, create these files)
// For single file, inline them
function ai_optimizer_inline_assets() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#ai-optimize-btn').click(function() {
            var content = $('#content').val();
            if (!content) return;
            $.post(aiOptimizer.ajaxurl, {
                action: 'optimize_content',
                nonce: aiOptimizer.nonce,
                content: content
            }, function(resp) {
                if (resp.success) {
                    $('#content').val(resp.content);
                } else {
                    alert(resp.error);
                }
            });
        });
    });
    </script>
    <style>
    #ai-optimize-btn { background: #0073aa; color: white; padding: 10px; margin: 10px 0; border: none; cursor: pointer; }
    #ai-optimize-btn:hover { background: #005a87; }
    .premium-notice { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
    </style>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_assets');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_assets');

// Admin page template
function ai_optimizer_admin_template() {
    $usage_left = 5 - get_option('ai_content_optimizer_usage', 0);
    echo '<div class="wrap"><h1>AI Content Optimizer</h1>';
    echo '<form method="post"><label>Enter OpenAI API Key: </label><input type="text" name="api_key" value="' . esc_attr(get_option('ai_content_optimizer_api_key')) . '" style="width:300px;"><input type="submit" value="Save" class="button-primary"></form>';
    echo '<p>Add button to post editor for one-click optimization. Premium unlocks unlimited uses.</p>';
    if (!$this->is_premium && $usage_left <= 0) {
        echo '<div class="premium-notice">Free uses exhausted. <a href="' . esc_url('https://example.com/premium') . '" target="_blank">Upgrade to Premium</a></div>';
    }
    echo '</div>';
}
// Note: In full implementation, use proper template file.
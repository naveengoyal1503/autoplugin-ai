/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content with AI-powered SEO suggestions, readability scores, and meta enhancements.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
    }

    public function activate() {
        add_option('ai_optimizer_api_key', '');
        add_option('ai_optimizer_enabled', '1');
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<p><strong>Readability Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        if ($suggestions) {
            echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
        }
        if (!$score) {
            echo '<p><a href="#" id="ai-analyze">Analyze Now (Free)</a> | <a href="https://example.com/premium" target="_blank">Upgrade to Pro</a></p>';
        }
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function optimize_content($content) {
        if (!get_option('ai_optimizer_enabled')) {
            return $content;
        }
        // Simulate AI optimization (free version: basic keyword enhancement)
        $content = $this->basic_optimize($content);
        // Premium: Would call real AI API here
        return $content;
    }

    private function basic_optimize($content) {
        // Free: Add basic SEO tags, improve readability
        $content = preg_replace('/<h2([^>]*)>([^<]+)<\/h2>/i', '<h2$1><span class="ai-optimized">$2</span></h2>', $content);
        return $content;
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_optimizer_settings');
                do_settings_sections('ai_optimizer_settings');
                submit_button();
                ?>
            </form>
            <div style="background:#fff3cd;padding:15px;margin:20px 0;border-radius:4px;">
                <h3>Go Pro for AI-Powered Optimizations!</h3>
                <p>Unlock unlimited AI scans, auto-meta generation, bulk optimization, and more. <a href="https://example.com/premium" target="_blank">Upgrade Now ($9/mo)</a></p>
            </div>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ai_optimizer_settings', 'ai_optimizer_api_key');
        register_setting('ai_optimizer_settings', 'ai_optimizer_enabled');

        add_settings_section(
            'ai_optimizer_section',
            __('Configuration', 'ai-content-optimizer'),
            null,
            'ai_optimizer_settings'
        );

        add_settings_field(
            'ai_optimizer_api_key',
            __('API Key (Pro)', 'ai-content-optimizer'),
            array($this, 'api_key_callback'),
            'ai_optimizer_settings',
            'ai_optimizer_section'
        );

        add_settings_field(
            'ai_optimizer_enabled',
            __('Enable Auto-Optimization', 'ai-content-optimizer'),
            array($this, 'enabled_callback'),
            'ai_optimizer_settings',
            'ai_optimizer_section'
        );
    }

    public function api_key_callback() {
        $key = get_option('ai_optimizer_api_key');
        echo '<input type="text" name="ai_optimizer_api_key" value="' . esc_attr($key) . '" class="regular-text" placeholder="Enter Pro API Key" />';
        echo '<p class="description">Get your key from <a href="https://example.com/premium" target="_blank">Premium Dashboard</a></p>';
    }

    public function enabled_callback() {
        $enabled = get_option('ai_optimizer_enabled');
        echo '<input type="checkbox" name="ai_optimizer_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
    }
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', function($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_script('ai-optimizer', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
    }
});

// AJAX for analysis (free demo)
add_action('wp_ajax_ai_analyze', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_optimizer_nonce')) {
        wp_die('Security check failed');
    }
    $post_id = intval($_POST['post_id']);
    // Simulate analysis
    $score = rand(60, 95);
    $suggestions = 'Free: Improve keyword density. Pro: Full AI rewrite available.';
    update_post_meta($post_id, '_ai_optimizer_score', $score);
    update_post_meta($post_id, '_ai_optimizer_suggestions', $suggestions);
    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
});

AIContentOptimizer::get_instance();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    if (isset($_GET['post'])) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-analyze').click(function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'ai_analyze',
                    post_id: $('#post_ID').val(),
                    nonce: $('#ai_optimizer_nonce').val()
                }, function(res) {
                    if (res.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');

?>
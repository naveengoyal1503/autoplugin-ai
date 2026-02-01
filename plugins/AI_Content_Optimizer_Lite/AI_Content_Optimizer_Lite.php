/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress post content for better SEO using AI-powered suggestions. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_meta_box_data'));
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $optimized = get_post_meta($post->ID, '_ai_optimized', true);
        echo '<p><strong>AI Suggestions:</strong></p>';
        echo '<textarea id="ai-suggestions" rows="6" cols="20" readonly>' . esc_textarea(get_post_meta($post->ID, '_ai_suggestions', true)) . '</textarea>';
        echo '<br><button type="button" id="optimize-btn" class="button button-primary">Optimize Content (Free: 3/day)</button>';
        echo '<p id="limit-msg" style="display:none;color:red;">Free limit reached. <a href="#" id="upgrade-link">Upgrade to Premium</a></p>';
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['_ai_suggestions'])) {
            update_post_meta($post_id, '_ai_suggestions', sanitize_textarea_field($_POST['_ai_suggestions']));
        }
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <p>Free version: Basic SEO analysis (limited to 3 scans/day).</p>
            <p><strong>Premium Features:</strong> Unlimited scans, AI rewrites, keyword research, integrations.</p>
            <a href="https://example.com/premium" class="button button-primary" target="_blank">Upgrade to Premium</a>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 'settings_page_ai-content-optimizer' !== $hook) {
            return;
        }
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_ajax'),
        ));
    }

    public function handle_optimize_content() {
        check_ajax_referer('ai_optimizer_ajax', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate daily limit (in real: use transients or user meta)
        $today = date('Y-m-d');
        $usage = get_transient('ai_optimizer_usage_' . get_current_user_id()) ?: 0;
        if ($usage >= 3) {
            wp_send_json_error('Free limit reached. Upgrade to premium for unlimited access.');
        }

        // Mock AI analysis (in premium: integrate real AI API like OpenAI)
        $suggestions = $this->mock_ai_analysis($content);

        set_transient('ai_optimizer_usage_' . get_current_user_id(), $usage + 1, DAY_IN_SECONDS);

        update_post_meta($post_id, '_ai_suggestions', $suggestions);
        update_post_meta($post_id, '_ai_optimized', true);

        wp_send_json_success(array('suggestions' => $suggestions));
    }

    private function mock_ai_analysis($content) {
        $length = strlen($content);
        $keywords = $this->extract_keywords($content);
        return "SEO Score: " . ($length > 500 ? '85/100' : '60/100') . "\n"
             . "Suggestions:\n"
             . "- Length: " . ($length > 1000 ? 'Good' : 'Add more content') . "\n"
             . "- Keywords: " . implode(', ', array_slice($keywords, 0, 5)) . "\n"
             . "- Add headings and lists for better readability.\n"
             . "Premium: Get full rewrite and keyword suggestions.";
    }

    private function extract_keywords($content) {
        // Simple keyword extraction mock
        $words = explode(' ', strtolower($content));
        $common = array('the', 'and', 'to', 'of', 'in');
        return array_filter($words, function($word) use ($common) {
            return strlen($word) > 4 && !in_array($word, $common);
        });
    }

    public function activate() {
        // Activation logic
    }

    public function deactivate() {
        // Deactivation logic
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
function ai_optimizer_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'edit-post') {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for unlimited optimizations and AI rewrites! <a href="https://example.com/premium">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_optimizer_admin_notice');

// JS file would be enqueued, but for single-file, inline it
function ai_optimizer_inline_js() {
    if (!is_admin()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#optimize-btn').click(function() {
            var post_id = $('#post_ID').val();
            $('#optimize-btn').prop('disabled', true).text('Optimizing...');
            $.post(ai_ajax.ajax_url, {
                action: 'optimize_content',
                post_id: post_id,
                nonce: ai_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $('#ai-suggestions').val(response.data.suggestions);
                } else {
                    $('#limit-msg').show();
                    $('#upgrade-link').click(function() {
                        window.open('https://example.com/premium', '_blank');
                    });
                }
                $('#optimize-btn').prop('disabled', false).text('Optimize Content (Free: 3/day)');
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');

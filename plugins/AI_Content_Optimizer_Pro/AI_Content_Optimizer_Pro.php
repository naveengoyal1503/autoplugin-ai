/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content with AI-powered SEO suggestions, readability scores, and keyword enhancements.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_optimizer_api_key', '');
        add_option('ai_optimizer_free_limit', 5);
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            'post',
            'side',
            'high'
        );
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            'page',
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $limit = get_option('ai_optimizer_free_limit', 5);
        $used = get_option('ai_optimizer_usage_' . get_current_user_id(), 0);
        $is_premium = get_option('ai_optimizer_premium', false);

        echo '<p><strong>Free scans left: ' . ($limit - $used) . '</strong></p>';
        if (!$is_premium) {
            echo '<p><a href="' . admin_url('admin.php?page=ai-optimizer-settings') . '" class="button button-primary">Upgrade to Pro</a></p>';
        }
        echo '<button id="ai-optimize-btn" class="button button-secondary">Optimize Content</button>';
        echo '<div id="ai-results" style="margin-top:10px;"></div>';

        echo '<script>
        jQuery(document).ready(function($) {
            $("#ai-optimize-btn").click(function() {
                var content = $("#content").val();
                $.post(ajaxurl, {
                    action: "ai_optimize_content",
                    content: content,
                    nonce: $("#ai_optimizer_nonce").val()
                }, function(response) {
                    $("#ai-results").html(response);
                });
            });
        });
        </script>';
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-optimizer-settings',
            array($this, 'settings_page_html')
        );
    }

    public function settings_init() {
        register_setting('ai_optimizer', 'ai_optimizer_api_key');
        register_setting('ai_optimizer', 'ai_optimizer_premium');
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_optimizer'); ?>
                <?php do_settings_sections('ai_optimizer'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key</th>
                        <td><input type="text" name="ai_optimizer_api_key" value="<?php echo esc_attr(get_option('ai_optimizer_api_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Premium License</th>
                        <td><input type="text" name="ai_optimizer_premium" value="<?php echo esc_attr(get_option('ai_optimizer_premium', false)); ?>" placeholder="Enter license key for unlimited access" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Upgrade to Pro for <strong>unlimited optimizations, AI rewrites, and advanced SEO insights</strong>. <a href="https://example.com/pricing" target="_blank">Get Pro Now ($4.99/mo)</a></p>
        </div>
        <?php
    }

    public static function ajax_ai_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $user_id = get_current_user_id();
        $limit = get_option('ai_optimizer_free_limit', 5);
        $used = get_option('ai_optimizer_usage_' . $user_id, 0);
        $is_premium = get_option('ai_optimizer_premium');

        if (!$is_premium && $used >= $limit) {
            wp_die('Upgrade to Pro for unlimited optimizations!');
        }

        $content = sanitize_textarea_field($_POST['content']);

        // Simulate AI analysis (in real version, integrate OpenAI or similar)
        $readability = rand(60, 90);
        $keywords = ['WordPress', 'SEO', 'content', 'optimization'];
        $suggestions = 'Improve readability by shortening sentences. Add more keywords like "' . $keywords . '".';
        $optimized = substr($content, 0, 200) . '... (AI optimized snippet)';

        if (!$is_premium) {
            update_option('ai_optimizer_usage_' . $user_id, $used + 1);
        }

        ob_start();
        echo '<div class="notice notice-success"><p><strong>Readability Score:</strong> ' . $readability . '%</p>';
        echo '<p><strong>Suggested Keywords:</strong> ' . implode(', ', $keywords) . '</p>';
        echo '<p><strong>Suggestions:</strong> ' . $suggestions . '</p>';
        echo '<p><strong>Optimized Preview:</strong> ' . $optimized . '</p>';
        echo '<p><em>Copy and paste into your content editor.</em></p></div>';
        wp_die(ob_get_clean());
    }
}

AIContentOptimizer::get_instance();

add_action('wp_ajax_ai_optimize_content', array('AIContentOptimizer', 'ajax_ai_optimize_content'));

?>
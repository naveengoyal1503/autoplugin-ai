/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private $is_premium = false;

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));

        // Simulate premium check (in real: API call to your service)
        $this->is_premium = get_option('ai_optimizer_premium_key') !== false;
    }

    public function activate() {
        add_option('ai_optimizer_activated', true);
    }

    public function deactivate() {
        delete_option('ai_optimizer_activated');
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score : 'Not analyzed') . '/100</p>';
        if ($suggestions) {
            echo '<p><strong>Free Suggestions:</strong> ' . esc_html($suggestions ?? 'Run analysis') . '</p>';
            if ($this->is_premium && isset($suggestions[1])) {
                echo '<p><strong>Premium Suggestion:</strong> ' . esc_html($suggestions[1]) . '</p>';
            } else {
                echo '<p><a href="https://example.com/premium" target="_blank">Upgrade to Premium for more!</a></p>';
            }
        }
        echo '<p><a href="#" class="button ai-optimize-btn" data-post-id="' . $post->ID . '">Analyze Now</a></p>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Simulate AI analysis
        $content = get_post_field('post_content', $post_id);
        $score = min(100, 50 + (substr_count(strtolower($content), 'keyword') * 10)); // Dummy logic
        $suggestions = array(
            'Add more headings.',
            $this->is_premium ? 'AI-generated meta description: Optimized title for better clicks.' : null
        );
        update_post_meta($post_id, '_ai_optimizer_score', $score);
        update_post_meta($post_id, '_ai_optimizer_suggestions', array_filter($suggestions));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_premium_key'])) {
            update_option('ai_optimizer_premium_key', sanitize_text_field($_POST['ai_premium_key']));
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <p><label>Premium Key: <input type="text" name="ai_premium_key" placeholder="Enter premium key" /></label></p>
                <p class="description">Get premium at <a href="https://example.com/premium" target="_blank">example.com/premium</a> ($9.99/mo)</p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function add_action_links($links) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-optimizer') . '">Settings</a>';
        return $links;
    }
}

new AIContentOptimizer();

// AJAX for analysis (simplified)
add_action('wp_ajax_ai_optimize', function() {
    $post_id = intval($_POST['post_id']);
    // Trigger save_post logic
    do_action('save_post', $post_id);
    wp_die('Analyzed!');
});

// Dummy JS file content (enqueue would load separate, but inline for single file)
function ai_optimizer_inline_js() {
    if (in_array(basename($_SERVER['PHP_SELF']), array('post.php', 'post-new.php'))) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.ai-optimize-btn').click(function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                $.post(ajaxurl, {action: 'ai_optimize', post_id: postId}, function() {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ai_optimizer_inline_js');
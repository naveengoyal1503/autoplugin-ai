/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes post content for SEO using AI-powered suggestions. Freemium model with premium upgrades.
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

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Check for premium license (simulate with option)
        $this->is_premium = get_option('ai_optimizer_premium') === 'activated';
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_optimizer_analyzed', array());
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_optimizer_box',
            'AI Content Optimizer',
            array($this, 'meta_box_html'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_html($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $analyzed = get_post_meta($post->ID, '_ai_optimized_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_suggestions', true);
        echo '<p><strong>SEO Score:</strong> ' . ($analyzed ? esc_html($analyzed) . '%' : 'Not analyzed') . '</p>';
        if ($suggestions) {
            echo '<p><em>Suggestions:</em> ' . esc_html($suggestions) . '</p>';
        }
        if (!$this->is_premium) {
            echo '<p><a href="#" class="button button-primary" onclick="alert(\'Upgrade to Premium for bulk optimize and advanced AI!\')">Upgrade to Pro</a></p>';
        }
        echo '<button id="ai-optimize-btn" class="button" data-post-id="' . $post->ID . '">Analyze Now</button>';
    }

    public function save_meta($post_id) {
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

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_premium_key'])) {
            update_option('ai_optimizer_premium', sanitize_text_field($_POST['ai_premium_key']));
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('ai_settings_nonce'); ?>
                <p><label>Premium License Key: <input type="text" name="ai_premium_key" value="<?php echo esc_attr(get_option('ai_optimizer_premium')); ?>" /></label></p>
                <?php submit_button(); ?>
            </form>
            <p><?php echo $this->is_premium ? 'Premium active. Enjoy advanced features!' : 'Enter license for premium (demo: use "premium123").'; ?></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        if (is_single()) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'ai-admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin-js', 'ai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_nonce')));
        }
    }
}

// AJAX handler for analysis
add_action('wp_ajax_ai_optimize', 'AIContentOptimizer::handle_optimize');
AIContentOptimizer::handle_optimize = function() {
    check_ajax_referer('ai_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die();
    }

    // Simulate AI analysis (in real: integrate OpenAI API)
    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $score = min(95, 50 + ($word_count / 10) + rand(0, 20)); // Basic sim
    $suggestions = $AIContentOptimizer::is_premium ?
        'Optimized keywords, meta, readability. Premium bulk ready.' :
        'Add keywords, improve readability. Upgrade for AI full analysis.';

    update_post_meta($post_id, '_ai_optimized_score', $score);
    update_post_meta($post_id, '_ai_suggestions', $suggestions);

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
};

AIContentOptimizer::get_instance();

// Dummy JS files content (base64 or inline, but for single file, use inline)
/*
For ai-admin.js:
 jQuery(document).ready(function($) {
    $('#ai-optimize-btn').click(function(e) {
        e.preventDefault();
        var postId = $(this).data('post-id');
        $.post(ai_ajax.ajax_url, {
            action: 'ai_optimize',
            post_id: postId,
            nonce: ai_ajax.nonce
        }, function(res) {
            if (res.success) {
                alert('Score: ' + res.data.score + '%\nSuggestions: ' + res.data.suggestions);
                location.reload();
            }
        });
    });
 });
*/

// Note: For production, use actual AI API like OpenAI. This is a functional demo with simulated analysis.
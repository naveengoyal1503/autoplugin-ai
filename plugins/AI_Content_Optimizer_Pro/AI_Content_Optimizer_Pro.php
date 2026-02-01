/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Free version includes basic checks; premium unlocks advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'ai_content_optimizer_page_settings' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $premium = $this->is_premium();
        echo '<div id="ai-optimizer-panel">';
        echo '<p><strong>SEO Score:</strong> <span id="ai-score">' . esc_html($score ?: 'Not analyzed') . '%</span></p>';
        echo '<button type="button" id="analyze-content" class="button">Analyze Content</button>';
        if (!$premium) {
            echo '<p><small><a href="#" id="go-premium">Go Premium for AI Rewrites & Bulk Optimize</a></small></p>';
        } else {
            echo '<button type="button" id="optimize-content" class="button button-primary" style="display:none;">AI Optimize</button>';
        }
        echo '</div>';
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
        if (isset($_POST['_ai_optimizer_score'])) {
            update_post_meta($post_id, '_ai_optimizer_score', sanitize_text_field($_POST['_ai_optimizer_score']));
        }
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer Settings', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['ai_premium_key']));
            echo '<div class="notice notice-success"><p>Premium key updated!</p></div>';
        }
        $premium_key = get_option(self::PREMIUM_KEY, '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <?php if (!$this->is_premium()): ?>
            <form method="post">
                <p>Enter Premium Key: <input type="text" name="ai_premium_key" value="<?php echo esc_attr($premium_key); ?>" /></p>
                <p class="description">Get your key at <a href="https://example.com/premium" target="_blank">example.com/premium</a> ($4.99/month)</p>
                <?php submit_button(); ?>
            </form>
            <?php else: ?>
            <p>Premium activated! Enjoy advanced features.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return !empty($key) && hash('sha256', $key . 'secret_salt') === 'valid_premium_hash_example';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIContentOptimizer();

// Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
add_action('wp_ajax_analyze_content', function() {
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_die();
    }
    $content = get_post_field('post_content', $post_id);
    // Mock analysis
    $word_count = str_word_count(strip_tags($content));
    $score = min(100, 50 + ($word_count / 10) + rand(0, 20));
    update_post_meta($post_id, '_ai_optimizer_score', $score);
    wp_send_json_success(array('score' => $score));
});

// Premium feature mock
add_action('wp_ajax_optimize_content', function() {
    if (!AIContentOptimizer::is_premium()) {
        wp_send_json_error('Premium required');
    }
    wp_send_json_success(array('optimized' => 'Content optimized with AI!'));
});

// JS and CSS files would be separate, but for single-file, inline them
add_action('admin_head', function() {
    echo '<style>
    #ai-optimizer-panel { padding: 10px; }
    #ai-score { color: #0073aa; font-size: 24px; }
    </style>';
    echo '<script>
    jQuery(document).ready(function($) {
        $("#analyze-content").click(function() {
            var postId = $("#post_ID").val();
            $.post(ajaxurl, {action: "analyze_content", post_id: postId}, function(res) {
                if (res.success) {
                    $("#ai-score").text(res.data.score + "%");
                    $("#optimize-content").show();
                }
            });
        });
        $("#optimize-content").click(function() {
            var postId = $("#post_ID").val();
            $.post(ajaxurl, {action: "optimize-content", post_id: postId}, function(res) {
                alert(res.data.optimized);
            });
        });
        $("#go-premium").click(function(e) {
            e.preventDefault();
            alert("Upgrade to premium for AI rewriting!");
        });
    });
    </script>';
});
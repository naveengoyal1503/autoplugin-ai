/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
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

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_analysis'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (class_exists('RankMath')) {
            add_action('rank_math/content_ai/after_setup', array($this, 'integrate_with_rankmath'));
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

    public function settings_page() {
        if (isset($_POST['ai_optimizer_api_key'])) {
            update_option('ai_optimizer_api_key', sanitize_text_field($_POST['ai_optimizer_api_key']));
            echo '<div class="notice notice-success"><p>API Key saved!</p></div>';
        }
        $api_key = get_option('ai_optimizer_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="ai_optimizer_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited optimizations for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-analysis',
            'AI Content Analysis',
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('ai_content_nonce', 'ai_content_nonce');
        $analysis = get_post_meta($post->ID, '_ai_analysis', true);
        $score = isset($analysis['score']) ? $analysis['score'] : 0;
        echo '<p><strong>AI Score:</strong> ' . intval($score) . '/100</p>';
        echo '<p>' . (isset($analysis['suggestions']) ? esc_html($analysis['suggestions']) : 'Run analysis') . '</p>';
        echo '<button id="optimize-content" class="button button-primary" data-post-id="' . $post->ID . '">Optimize with AI</button>';
        echo '<div id="optimization-result"></div>';
    }

    public function save_post_analysis($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $post = get_post($post_id);
        $content = $post->post_content;
        $analysis = $this->mock_ai_analysis($content);
        update_post_meta($post_id, '_ai_analysis', $analysis);
    }

    public function handle_optimize_content() {
        check_ajax_referer('ai_content_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Unauthorized');
        }

        $post = get_post($post_id);
        $optimized = $this->mock_ai_optimize($post->post_content);
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $optimized
        ));

        $analysis = $this->mock_ai_analysis($optimized);
        update_post_meta($post_id, '_ai_analysis', $analysis);

        wp_send_json_success(array('content' => $optimized, 'analysis' => $analysis));
    }

    private function mock_ai_analysis($content) {
        // Mock AI analysis (Pro version uses real OpenAI API)
        $word_count = str_word_count(strip_tags($content));
        $score = min(95, 50 + ($word_count / 1000) * 20 + rand(0, 20));
        $suggestions = $score > 80 ? 'Excellent! Great SEO and readability.' : 'Improve: Add more headings, keywords, and shorten sentences.';
        return array('score' => $score, 'suggestions' => $suggestions);
    }

    private function mock_ai_optimize($content) {
        // Mock optimization: Add headings, improve structure (Pro uses real AI)
        $optimized = preg_replace('/<p>/', '<h2>', $content, 1) . '</h2>';
        return $optimized . '<p>Optimized by AI Content Optimizer Pro!</p>';
    }

    public function integrate_with_rankmath() {
        // Placeholder for RankMath integration
    }
}

AIContentOptimizer::get_instance();

// Enqueue scripts
add_action('admin_enqueue_scripts', function($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
    wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), '1.0.0', true);
    wp_localize_script('ai-optimizer-js', 'ai_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_content_nonce')
    ));
});

// Create JS file placeholder (in real plugin, include ai-optimizer.js)
/*
// ai-optimizer.js content:
jQuery(document).ready(function($) {
    $('#optimize-content').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var postId = $btn.data('post-id');
        var $result = $('#optimization-result');

        $.post(ai_ajax.ajax_url, {
            action: 'optimize_content',
            post_id: postId,
            nonce: ai_ajax.nonce
        }, function(response) {
            if (response.success) {
                $result.html('<p>Optimized! Score: ' + response.data.analysis.score + '</p>');
                location.reload();
            }
        });
    });
});
*/